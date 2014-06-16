<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

class ConfigurableTableDataConverter extends AbstractTableDataConverter implements EntityNameAwareInterface
{
    const DEFAULT_SINGLE_RELATION_LEVEL = 5;
    const DEFAULT_MULTIPLE_RELATION_LEVEL = 3;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var RelationCalculator
     */
    protected $relationCalculator;

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

            list($this->headerConversionRules, $this->backendHeader)
                = array($this->processCollectionRegexp($headerConversionRules), $backendHeader);
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
     * @param $entityName
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

        $rules = array();
        $backendHeaders = array();
        $defaultOrder = 10000;

        // generate conversion rules and backend header
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded')) {
                continue;
            }

            // get import/export config parameters
            $fieldHeader = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'header', $field['label']);

            $fieldOrder = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'order');
            if ($fieldOrder === null || $fieldOrder === '') {
                $fieldOrder = $defaultOrder;
                $defaultOrder++;
            }
            $fieldOrder = (int)$fieldOrder;

            // process relations
            if ($this->fieldHelper->isRelation($field)) {
                $isSingleRelation = $this->fieldHelper->isSingleRelation($field) && $singleRelationDeepLevel > 0;
                $isMultipleRelation = $this->fieldHelper->isMultipleRelation($field) && $multipleRelationDeepLevel > 0;

                // if relation must be included
                if ($fullData && ($isSingleRelation || $isMultipleRelation)) {
                    $relatedEntityName = $field['related_entity_name'];
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
                    $rules = array_merge($rules, $relationRules);

                    $relationBackendHeaders = $this->buildBackendHeaders(
                        $relationBackendHeaders,
                        $isSingleRelation,
                        $isMultipleRelation,
                        $entityName,
                        $fieldName,
                        $fieldOrder
                    );
                    $backendHeaders = array_merge($backendHeaders, $relationBackendHeaders);
                }
            } else {
                // process scalars
                if ($fullData || $this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity')) {
                    $rules[$fieldHeader] = array('value' => $fieldName, 'order' => $fieldOrder);
                    $backendHeaders[] = $rules[$fieldHeader];
                }
            }
        }

        return array($this->sortData($rules), $this->sortData($backendHeaders));
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
        $rules = array();

        foreach ($relationRules as $header => $name) {
            // single relation
            if ($isSingleRelation) {
                $relationHeader = $fieldHeader . ' ' . $header;
                $relationName = $fieldName . $delimiter . $name;
                $rules[$relationHeader] = array(
                    'value' => $relationName,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++,
                );
            } elseif ($isMultipleRelation) {
                // multiple relation
                $frontendHeader = $fieldHeader . ' (\d+) ' . $header;
                $backendHeader
                    = $fieldName . $delimiter . '(\d+)' . $delimiter . $name;
                $rules[$frontendHeader] = array(
                    'value' => $backendHeader,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++,
                );
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
        $backendHeaders = array();

        // single relation
        if ($isSingleRelation) {
            foreach ($relationBackendHeaders as $header) {
                $backendHeaders[] = array(
                    'value' => $fieldName . $delimiter . $header,
                    'order' => $fieldOrder,
                    'subOrder' => $subOrder++,
                );
            }
        } elseif ($isMultipleRelation) {
            // multiple relation
            $maxEntities = $this->relationCalculator->getMaxRelatedEntities($entityName, $fieldName);
            for ($i = 0; $i < $maxEntities; $i++) {
                foreach ($relationBackendHeaders as $header) {
                    $backendHeaders[] = array(
                        'value' => $fieldName . $delimiter . $i . $delimiter . $header,
                        'order' => $fieldOrder,
                        'subOrder' => $subOrder++,
                    );
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
            if (strpos($frontendHeader, '(\d+)') !== false) {
                $rules[$frontendHeader] = array(
                    self::FRONTEND_TO_BACKEND => array(
                        $frontendHeader,
                        $this->getReplaceCallback($backendHeader, -1)
                    ),
                    self::BACKEND_TO_FRONTEND => array(
                        $backendHeader,
                        $this->getReplaceCallback($frontendHeader, +1)
                    ),
                );
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
            $parts = explode('(\d+)', $string);

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
     * @param int $a
     * @param int $b
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
        uasort($rules, array($this, 'sortDataCallback'));

        // clear unused data
        foreach ($rules as $label => $data) {
            $rules[$label] = $data['value'];
        }

        return $rules;
    }
}
