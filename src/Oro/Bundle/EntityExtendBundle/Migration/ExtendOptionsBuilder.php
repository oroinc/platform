<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendOptionsBuilder
{
    /** @var EntityMetadataHelper */
    protected $entityMetadataHelper;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var array */
    protected $tableToEntityMap = [];

    /** @var array */
    protected $result = [];

    /**
     * @param EntityMetadataHelper $entityMetadataHelper
     * @param FieldTypeHelper      $fieldTypeHelper
     */
    public function __construct(
        EntityMetadataHelper $entityMetadataHelper,
        FieldTypeHelper $fieldTypeHelper
    ) {
        $this->entityMetadataHelper = $entityMetadataHelper;
        $this->fieldTypeHelper      = $fieldTypeHelper;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->result;
    }

    /**
     * @param string $tableName
     * @param array  $options
     */
    public function addTableOptions($tableName, array $options)
    {
        $customEntityClassName = $this->getAndRemoveOption($options, ExtendOptionsManager::ENTITY_CLASS_OPTION);
        $entityClassName       = $this->getEntityClassName($tableName, $customEntityClassName, false);
        if (!$entityClassName) {
            return;
        }

        $tableMode = $this->getAndRemoveOption($options, ExtendOptionsManager::MODE_OPTION);

        if (!empty($options)) {
            $this->result[$entityClassName]['configs'] = $options;
        }
        if ($tableMode) {
            $this->result[$entityClassName]['mode'] = $tableMode;
        }
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param array  $options
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addColumnOptions($tableName, $columnName, $options)
    {
        $entityClassName = $this->getEntityClassName($tableName, null, false);
        if (!$entityClassName) {
            return;
        }

        $newColumnName = $this->getAndRemoveOption($options, ExtendOptionsManager::NEW_NAME_OPTION);
        if ($newColumnName) {
            $this->result[ExtendConfigProcessor::RENAME_CONFIGS][$entityClassName][$columnName] = $newColumnName;
            if (empty($options)) {
                return;
            };
        }

        $fieldName = $this->getAndRemoveOption($options, ExtendOptionsManager::FIELD_NAME_OPTION);
        if (!$fieldName) {
            $fieldName = $this->getFieldName($tableName, $columnName);
        }
        $columnType           = $this->getAndRemoveOption($options, ExtendOptionsManager::TYPE_OPTION);
        $columnMode           = $this->getAndRemoveOption($options, ExtendOptionsManager::MODE_OPTION);
        $columnUnderlyingType = $this->fieldTypeHelper->getUnderlyingType($columnType);

        if (in_array($columnUnderlyingType, ['oneToMany', 'manyToOne', 'manyToMany'])) {
            if (!isset($options['extend'])) {
                $options['extend'] = [];
            }
            $target = $this->getAndRemoveOption($options, ExtendOptionsManager::TARGET_OPTION);
            foreach ($target as $optionName => $optionValue) {
                switch ($optionName) {
                    case 'table_name':
                        $options['extend']['target_entity'] = $this->getEntityClassName($optionValue);
                        break;
                    case 'column':
                        $options['extend']['target_field'] = $this->getFieldName($target['table_name'], $optionValue);
                        break;
                    case 'columns':
                        foreach ($optionValue as $group => $columns) {
                            $values = [];
                            foreach ($columns as $column) {
                                $values[] = $this->getFieldName($target['table_name'], $column);
                            }
                            $options['extend']['target_' . $group] = $values;
                        }
                        break;
                }
            }

            $options['extend']['relation_key'] = ExtendHelper::buildRelationKey(
                $entityClassName,
                $fieldName,
                $columnUnderlyingType,
                $options['extend']['target_entity']
            );
        }

        $this->result[$entityClassName]['fields'][$fieldName] = [];
        if (!empty($options)) {
            $this->result[$entityClassName]['fields'][$fieldName]['configs'] = $options;
        }
        if ($columnType) {
            $this->result[$entityClassName]['fields'][$fieldName]['type'] = $columnType;
        }
        if ($columnMode) {
            $this->result[$entityClassName]['fields'][$fieldName]['mode'] = $columnMode;
        }
    }

    /**
     * @param string $configType
     * @param string $tableName
     * @param array  $options
     */
    public function addTableAuxiliaryOptions($configType, $tableName, $options)
    {
        $entityClassName = $this->getEntityClassName($tableName, null, false);
        if (!$entityClassName) {
            return;
        }

        $this->result[$configType][$entityClassName]['configs'] = $options;
    }

    /**
     * @param string $configType
     * @param string $tableName
     * @param string $columnName
     * @param array  $options
     */
    public function addColumnAuxiliaryOptions($configType, $tableName, $columnName, $options)
    {
        $entityClassName = $this->getEntityClassName($tableName, null, false);
        if (!$entityClassName) {
            return;
        }

        $fieldName = $this->getFieldName($tableName, $columnName);

        $this->result[$configType][$entityClassName]['fields'][$fieldName] = $options;
    }

    /**
     * @param string $sectionName
     * @return string
     * @throws \RuntimeException if unknown section name specified
     */
    public function getAuxiliaryConfigType($sectionName)
    {
        switch ($sectionName) {
            case ExtendOptionsManager::APPEND_SECTION:
                return ExtendConfigProcessor::APPEND_CONFIGS;
            default:
                throw new \RuntimeException(sprintf('Unknown auxiliary section: %s.', $sectionName));
        }
    }

    /**
     * Gets an entity class name by its table name
     *
     * @param string $tableName
     * @param string $customEntityClassName The name of a custom entity
     * @param bool   $throwExceptionIfNotFound
     * @return string|null
     * @throws \RuntimeException if an entity class name was not found and $throwExceptionIfNotFound = TRUE
     */
    protected function getEntityClassName($tableName, $customEntityClassName = null, $throwExceptionIfNotFound = true)
    {
        if (!isset($this->tableToEntityMap[$tableName])) {
            $entityClassName = !empty($customEntityClassName)
                ? $customEntityClassName
                : $this->entityMetadataHelper->getEntityClassByTableName($tableName);
            if ($throwExceptionIfNotFound && empty($entityClassName)) {
                throw new \RuntimeException(sprintf('Cannot find entity for "%s" table.', $tableName));
            }
            $this->tableToEntityMap[$tableName] = $entityClassName;
        }

        $result = $this->tableToEntityMap[$tableName];
        if ($throwExceptionIfNotFound && empty($result)) {
            throw new \RuntimeException(sprintf('Cannot find entity for "%s" table.', $tableName));
        }

        return $result;
    }

    /**
     * Gets a field name by a column name
     *
     * @param string $tableName
     * @param string $columnName
     * @return string
     */
    protected function getFieldName($tableName, $columnName)
    {
        $fieldName = $this->entityMetadataHelper->getFieldNameByColumnName($tableName, $columnName);

        return $fieldName ? : $columnName;
    }

    /**
     * Gets a value of an option with the given name and then remove the option from $options array
     *
     * @param array  $options
     * @param string $name
     * @return mixed
     */
    protected function getAndRemoveOption(array &$options, $name)
    {
        $value = null;
        if (isset($options[$name])) {
            $value = $options[$name];
            unset($options[$name]);
        }

        return $value;
    }
}
