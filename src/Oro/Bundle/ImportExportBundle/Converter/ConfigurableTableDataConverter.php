<?php

namespace Oro\Bundle\ImportExportBundle\Converter;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

class ConfigurableTableDataConverter extends AbstractTableDataConverter implements EntityNameAwareInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var EntityFieldProvider
     */
    protected $fieldProvider;

    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @param EntityFieldProvider $fieldProvider
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(EntityFieldProvider $fieldProvider, ConfigProviderInterface $configProvider)
    {
        $this->fieldProvider = $fieldProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        $this->assertEntityName();

        return $this->getEntityRules($this->entityName, true, 2, 1);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return array_values($this->receiveHeaderConversionRules());
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
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
     * @param string $fieldName
     * @param string $parameter
     * @param mixed $default
     * @return mixed|null
     */
    protected function getConfigValue($entityName, $fieldName, $parameter, $default = null)
    {
        if (!$this->configProvider->hasConfig($entityName, $fieldName)) {
            return $default;
        }

        $fieldConfig = $this->configProvider->getConfig($entityName, $fieldName);
        if (!$fieldConfig->has($parameter)) {
            return $default;
        }

        return $fieldConfig->get($parameter);
    }

    /**
     * @param $entityName
     * @param bool $fullData
     * @param int $singleRelationDeepLevel
     * @param int $multipleRelationDeepLevel
     * @return array
     */
    protected function getEntityRules(
        $entityName,
        $fullData = false,
        $singleRelationDeepLevel = 0,
        $multipleRelationDeepLevel = 0
    ) {
        // get fields data
        $fields = $this->fieldProvider->getFields($entityName, true);

        // generate conversion rules
        $rules = array();
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if ($this->getConfigValue($entityName, $fieldName, 'excluded')) {
                continue;
            }

            $fieldHeader = $this->getConfigValue($entityName, $fieldName, 'header', $field['label']);

            $fieldOrder = $this->getConfigValue($entityName, $fieldName, 'order');
            if ($fieldOrder === null || $fieldOrder === '') {
                $fieldOrder = 10000;
            }
            $fieldOrder = (int)$fieldOrder;

            if ($this->isRelation($field)) {
                if ($fullData) {
                    $relatedEntityName = $field['related_entity_name'];
                    $fieldFullData = $this->getConfigValue($entityName, $fieldName, 'full', false);

                    if ($this->isSingleRelation($field) && $singleRelationDeepLevel > 0) {
                        // add fields rules from single relation
                        $relationRules = $this->getEntityRules(
                            $relatedEntityName,
                            $fieldFullData,
                            $singleRelationDeepLevel--,
                            $multipleRelationDeepLevel--
                        );

                        $subOrder = 0;
                        foreach ($relationRules as $header => $name) {
                            $relationHeader = $fieldHeader . ' ' . $header;
                            $relationName = $fieldName . $this->convertDelimiter . $name;
                            $rules[$relationHeader] = array(
                                'name' => $relationName,
                                'order' => $fieldOrder,
                                'subOrder' => $subOrder++,
                            );
                        }
                    } elseif ($this->isMultipleRelation($field) && $multipleRelationDeepLevel > 0) {
                        // TODO
                    }
                }
            } else {
                if ($fullData || $this->getConfigValue($entityName, $fieldName, 'identity')) {
                    $rules[$fieldHeader] = array('name' => $fieldName, 'order' => $fieldOrder);
                }
            }
        }

        return $this->sortRules($rules);
    }

    /**
     * @param int $a
     * @param int $b
     * @return int
     */
    protected function sortRulesCallback($a, $b)
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
    protected function sortRules(array $rules)
    {
        // sort fields by order
        uasort($rules, array($this, 'sortRulesCallback'));

        // clear unused data
        foreach ($rules as $label => $data) {
            $rules[$label] = $data['name'];
        }

        return $rules;
    }

    /**
     * @param array $field
     * @return bool
     */
    protected function isRelation(array $field)
    {
        return !empty($field['relation_type']) && !empty($field['related_entity_name']);
    }

    /**
     * @param array $field
     * @return bool
     */
    protected function isSingleRelation(array $field)
    {
        return $this->isRelation($field)
            && in_array($field['relation_type'], array('ref-one', 'oneToOne', 'manyToOne'));
    }

    /**
     * @param array $field
     * @return bool
     */
    protected function isMultipleRelation(array $field)
    {
        return $this->isRelation($field)
            && in_array($field['relation_type'], array('ref-many', 'oneToMany', 'manyToMany'));
    }
}
