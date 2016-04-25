<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

class ConfigurableTableDataConverter extends AbstractTableDataConverter implements EntityNameAwareInterface
{
    const DEFAULT_SINGLE_RELATION_LEVEL = 5;
    const DEFAULT_MULTIPLE_RELATION_LEVEL = 3;
    const DEFAULT_ORDER = 10000;

    const CONVERSION_TYPE_DATA = 'data';
    const CONVERSION_TYPE_FIXTURES = 'fixtures';

    /** @var string */
    protected $entityName;

    /**  @var FieldHelper */
    protected $fieldHelper;

    /** @var RelationCalculator */
    protected $relationCalculator;

    /** @var string */
    protected $relationDelimiter = ' ';

    /** @var string */
    protected $collectionDelimiter = '(\d+)';

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param FieldHelper $fieldHelper
     * @param RelationCalculatorInterface $relationCalculator
     */
    public function __construct(FieldHelper $fieldHelper, RelationCalculatorInterface $relationCalculator)
    {
        $this->fieldHelper = $fieldHelper;
        $this->relationCalculator = $relationCalculator;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Get field header
     *
     * @param string $entityClassName
     * @param string $initialFieldName
     * @param bool $isSearchingIdentityField
     * @return null|string
     */
    public function getFieldHeaderWithRelation($entityClassName, $initialFieldName, $isSearchingIdentityField = false)
    {
        $fields = $this->fieldHelper->getFields($entityClassName, true);

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $notFoundFieldName = !$isSearchingIdentityField && $fieldName !== $initialFieldName;
            $foundIdentifyField = $this->fieldHelper->getConfigValue($entityClassName, $fieldName, 'identity');
            $notFoundFieldIdentify = $isSearchingIdentityField && !$foundIdentifyField;

            if ($notFoundFieldName || $notFoundFieldIdentify) {
                continue;
            }

            if ($this->fieldHelper->isRelation($field) &&
                !$this->fieldHelper->processRelationAsScalar($entityClassName, $fieldName)) {
                return
                    $this->getFieldHeader($entityClassName, $field) .
                    $this->relationDelimiter .
                    $this->getFieldHeaderWithRelation($field['related_entity_name'], null, true);
            }

            return $this->getFieldHeader($entityClassName, $field);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        $this->initialize();

        return $this->headerConversionRules;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        $this->initialize();

        return $this->backendHeader;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * Receive backend header and header conversion rules simultaneously
     */
    protected function initialize()
    {
        if ($this->headerConversionRules === null || $this->backendHeader === null) {
            $this->assertEntityName();

            list($headerConversionRules, $backendHeader) = $this->getEntityRulesAndBackendHeaders(
                $this->entityName,
                true,
                self::DEFAULT_SINGLE_RELATION_LEVEL,
                self::DEFAULT_MULTIPLE_RELATION_LEVEL
            );

            list($this->headerConversionRules, $this->backendHeader) = [
                $this->processCollectionRegexp($headerConversionRules),
                $backendHeader
            ];
        }
    }

    /**
     * @throws LogicException
     */
    protected function assertEntityName()
    {
        if (!$this->entityName) {
            throw new LogicException('Entity class for data converter is not specified');
        }
    }

    /**
     * @param string $entityName
     * @param bool $fullData
     * @param int $singleRelationDeepLevel
     * @param int $multipleRelationDeepLevel
     * @return array
     */
    protected function getEntityRulesAndBackendHeaders(
        $entityName,
        $fullData = false,
        $singleRelationDeepLevel = 0,
        $multipleRelationDeepLevel = 0
    ) {
        // get fields data
        $fields = $this->fieldHelper->getFields($entityName, true);

        $rules = [];
        $backendHeaders = [];
        $defaultOrder = self::DEFAULT_ORDER;

        // generate conversion rules and backend header
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded')) {
                continue;
            }

            if ($fullData || $this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity')) {
                // get import/export config parameters
                $fieldHeader = $this->getFieldHeader($entityName, $field);

                $fieldOrder = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'order');
                if ($fieldOrder === null || $fieldOrder === '') {
                    $fieldOrder = $defaultOrder;
                    $defaultOrder++;
                }
                $fieldOrder = (int)$fieldOrder;

                // process relations
                if ($this->fieldHelper->isRelation($field)
                    && !$this->fieldHelper->processRelationAsScalar($entityName, $fieldName)
                ) {
                    list($relationRules, $relationBackendHeaders) = $this->getRelatedEntityRulesAndBackendHeaders(
                        $entityName,
                        $singleRelationDeepLevel,
                        $multipleRelationDeepLevel,
                        $field,
                        $fieldHeader,
                        $fieldOrder
                    );
                    $rules = array_merge($rules, $relationRules);
                    $backendHeaders = array_merge($backendHeaders, $relationBackendHeaders);
                } else {
                    // process scalars
                    $rules[$fieldHeader] = ['value' => $fieldName, 'order' => $fieldOrder];
                    $backendHeaders[] = $rules[$fieldHeader];
                }
            }
        }

        $event = $this->dispatchEntityRulesEvent($entityName, $backendHeaders, $rules, $fullData);

        return [$this->sortData($event->getRules()), $this->sortData($event->getHeaders())];
    }

    /**
     * @param array $relationRules
     * @param bool $isSingleRelation
     * @param bool $isMultipleRelation
     * @param string $fieldName
     * @param string $fieldHeader
     * @param int $fieldOrder
     * @return array
     */
    protected function buildRelationRules(
        array $relationRules,
        $isSingleRelation,
        $isMultipleRelation,
        $fieldName,
        $fieldHeader,
        $fieldOrder
    ) {
        $subOrder = 0;
        $delimiter = $this->convertDelimiter;
        $rules = [];

        foreach ($relationRules as $header => $name) {
            // single relation
            if ($isSingleRelation) {
                $relationHeader = $fieldHeader . $this->relationDelimiter . $header;
                $relationName = $fieldName . $delimiter . $name;
                $rules[$relationHeader] = [
                    'value' => $relationName,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++,
                ];
            } elseif ($isMultipleRelation) {
                // multiple relation
                $frontendCollectionDelimiter = $this->relationDelimiter
                    . $this->collectionDelimiter
                    . $this->relationDelimiter;
                $frontendHeader = $fieldHeader . $frontendCollectionDelimiter . $header;
                $backendHeader
                    = $fieldName . $delimiter . $this->collectionDelimiter . $delimiter . $name;
                $rules[$frontendHeader] = [
                    'value' => $backendHeader,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++,
                ];
            }
        }

        return $rules;
    }

    /**
     * @param array $relationBackendHeaders
     * @param bool $isSingleRelation
     * @param bool $isMultipleRelation
     * @param string $entityName
     * @param string $fieldName
     * @param int $fieldOrder
     * @return array
     */
    protected function buildBackendHeaders(
        array $relationBackendHeaders,
        $isSingleRelation,
        $isMultipleRelation,
        $entityName,
        $fieldName,
        $fieldOrder
    ) {
        $subOrder = 0;
        $delimiter = $this->convertDelimiter;
        $backendHeaders = [];

        // single relation
        if ($isSingleRelation) {
            foreach ($relationBackendHeaders as $header) {
                $backendHeaders[] = [
                    'value' => $fieldName . $delimiter . $header,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++,
                ];
            }
        } elseif ($isMultipleRelation) {
            // multiple relation
            $maxEntities = $this->relationCalculator->getMaxRelatedEntities($entityName, $fieldName);
            for ($i = 0; $i < $maxEntities; $i++) {
                foreach ($relationBackendHeaders as $header) {
                    $backendHeaders[] = [
                        'value' => $fieldName . $delimiter . $i . $delimiter . $header,
                        'order' => $fieldOrder,
                        'subOrder' => $subOrder++,
                    ];
                }
            }
        }

        return $backendHeaders;
    }

    /**
     * @param array $rules
     * @return array
     */
    protected function processCollectionRegexp(array $rules)
    {
        foreach ($rules as $frontendHeader => $backendHeader) {
            if (strpos($frontendHeader, $this->collectionDelimiter) !== false) {
                $rules[$frontendHeader] = [
                    self::FRONTEND_TO_BACKEND => [
                        $frontendHeader,
                        $this->getReplaceCallback($backendHeader, -1),
                    ],
                    self::BACKEND_TO_FRONTEND => [
                        $backendHeader,
                        $this->getReplaceCallback($frontendHeader, +1),
                    ],
                ];
            }
        }

        return $rules;
    }

    /**
     * @param string $string
     * @param int $shift
     * @return callable
     */
    protected function getReplaceCallback($string, $shift)
    {
        return function (array $matches) use ($string, $shift) {
            $result = '';
            $parts = explode($this->collectionDelimiter, $string);

            foreach ($parts as $index => $value) {
                $result .= $value;
                if ($index + 1 < count($parts)) {
                    $result .= ((int)$matches[$index + 1] + $shift);
                }
            }

            return $result;
        };
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortDataCallback($a, $b)
    {
        if ($a['order'] > $b['order']) {
            return 1;
        } elseif ($a['order'] < $b['order']) {
            return -1;
        } else {
            $aSub = isset($a['subOrder']) ? $a['subOrder'] : 0;
            $bSub = isset($b['subOrder']) ? $b['subOrder'] : 0;

            return $aSub > $bSub ? 1 : -1;
        }
    }

    /**
     * Uses key "order" to sort rules
     *
     * @param array $rules
     * @return array
     */
    protected function sortData(array $rules)
    {
        // sort fields by order
        uasort($rules, [$this, 'sortDataCallback']);

        // clear unused data
        foreach ($rules as $label => $data) {
            $rules[$label] = $data['value'];
        }

        return $rules;
    }

    /**
     * @param string $entityName
     * @param array $field
     * @return string
     */
    protected function getFieldHeader($entityName, $field)
    {
        $fieldHeader = $this->fieldHelper->getConfigValue($entityName, $field['name'], 'header', $field['label']);

        return $fieldHeader;
    }

    /**
     * @param string $entityName
     * @param int $singleRelationDeepLevel
     * @param int $multipleRelationDeepLevel
     * @param array $field
     * @param string $fieldHeader
     * @param int $fieldOrder
     *
     * @return array
     */
    protected function getRelatedEntityRulesAndBackendHeaders(
        $entityName,
        $singleRelationDeepLevel,
        $multipleRelationDeepLevel,
        $field,
        $fieldHeader,
        $fieldOrder
    ) {
        $relationRules = [];
        $relationBackendHeaders = [];

        $isSingleRelation = $this->fieldHelper->isSingleRelation($field) && $singleRelationDeepLevel > 0;
        $isMultipleRelation = $this->fieldHelper->isMultipleRelation($field) && $multipleRelationDeepLevel > 0;

        // if relation must be included
        if ($isSingleRelation || $isMultipleRelation) {
            $relatedEntityName = $field['related_entity_name'];
            $fieldName = $field['name'];
            $fieldFullData = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full', false);

            // process and merge relation rules and backend header for relation
            list($relationRules, $relationBackendHeaders) = $this->getEntityRulesAndBackendHeaders(
                $relatedEntityName,
                $fieldFullData,
                $singleRelationDeepLevel - 1,
                $multipleRelationDeepLevel - 1
            );

            $relationRules = $this->buildRelationRules(
                $relationRules,
                $isSingleRelation,
                $isMultipleRelation,
                $fieldName,
                $fieldHeader,
                $fieldOrder
            );

            $relationBackendHeaders = $this->buildBackendHeaders(
                $relationBackendHeaders,
                $isSingleRelation,
                $isMultipleRelation,
                $entityName,
                $fieldName,
                $fieldOrder
            );
        }

        return [$relationRules, $relationBackendHeaders];
    }

    /**
     * @return string
     */
    protected function getConversionType()
    {
        return $this->relationCalculator instanceof RelationCalculator
            ? static::CONVERSION_TYPE_DATA
            : static::CONVERSION_TYPE_FIXTURES;
    }

    /**
     * @param string $entityName
     * @param array $backendHeaders
     * @param array $rules
     * @param bool $fullData
     *
     * @return LoadEntityRulesAndBackendHeadersEvent
     */
    protected function dispatchEntityRulesEvent($entityName, $backendHeaders, array $rules, $fullData)
    {
        $event = new LoadEntityRulesAndBackendHeadersEvent(
            $entityName,
            $backendHeaders,
            $rules,
            $this->convertDelimiter,
            $this->getConversionType(),
            $fullData
        );
        if ($this->dispatcher && $this->dispatcher->hasListeners(Events::AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS)) {
            $this->dispatcher->dispatch(Events::AFTER_LOAD_ENTITY_RULES_AND_BACKEND_HEADERS, $event);
        }

        return $event;
    }
}
