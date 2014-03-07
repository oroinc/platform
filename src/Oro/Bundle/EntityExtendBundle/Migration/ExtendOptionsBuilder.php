<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendOptionsBuilder implements ExtendOptionsProviderInterface
{
    /**
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var array
     */
    protected $tableToEntityMap = [];

    /**
     * @var array
     */
    protected $result = [];

    /**
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
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
        $entityName = null;
        if (isset($options['_entity_name'])) {
            $entityName = $options['_entity_name'];
            unset($options['_entity_name']);
        }
        $entityClassName = $this->getEntityClassName($tableName, $entityName);

        $tableMode = isset($options[ExtendOptionsManager::MODE_OPTION])
            ? $options[ExtendOptionsManager::MODE_OPTION]
            : null;
        unset($options[ExtendOptionsManager::MODE_OPTION]);

        if (!isset($this->result[$entityClassName])) {
            $this->result[$entityClassName] = [];
        }
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
        $entityClassName = $this->getEntityClassName($tableName);

        $columnType = isset($options[ExtendOptionsManager::TYPE_OPTION])
            ? $options[ExtendOptionsManager::TYPE_OPTION]
            : null;
        unset($options[ExtendOptionsManager::TYPE_OPTION]);
        $columnMode = isset($options[ExtendOptionsManager::MODE_OPTION])
            ? $options[ExtendOptionsManager::MODE_OPTION]
            : null;
        unset($options[ExtendOptionsManager::MODE_OPTION]);

        if (in_array($columnType, ['oneToMany', 'manyToOne', 'manyToMany'])) {
            if (!isset($options['extend'])) {
                $options['extend'] = [];
            }
            foreach ($options[ExtendOptionsManager::TARGET_OPTION] as $optionName => $optionValue) {
                switch ($optionName) {
                    case 'table_name':
                        $options['extend']['target_entity'] = $this->getEntityClassName($optionValue);
                        break;
                    case 'column':
                        $options['extend']['target_field'] = $this->getFieldName(
                            $options[ExtendOptionsManager::TARGET_OPTION]['table_name'],
                            $optionValue
                        );
                        break;
                    case 'columns':
                        foreach ($optionValue as $group => $columns) {
                            $values = [];
                            foreach ($columns as $column) {
                                $values[] = $this->getFieldName(
                                    $options[ExtendOptionsManager::TARGET_OPTION]['table_name'],
                                    $column
                                );
                            }
                            $options['extend']['target_' . $group] = $values;
                        }
                        break;
                }
            }

            $options['extend']['relation_key'] = ExtendHelper::buildRelationKey(
                $entityClassName,
                $columnName,
                $columnType,
                $options['extend']['target_entity']
            );

            unset($options[ExtendOptionsManager::TARGET_OPTION]);
        }

        if (!isset($this->result[$entityClassName])) {
            $this->result[$entityClassName] = [];
        }
        if (!isset($this->result[$entityClassName]['fields'])) {
            $this->result[$entityClassName]['fields'] = [];
        }
        $this->result[$entityClassName]['fields'][$columnName] = [];
        if (!empty($options)) {
            $this->result[$entityClassName]['fields'][$columnName]['configs'] = $options;
        }
        if ($columnType) {
            $this->result[$entityClassName]['fields'][$columnName]['type'] = $columnType;
        }
        if ($columnMode) {
            $this->result[$entityClassName]['fields'][$columnName]['mode'] = $columnMode;
        }
    }

    /**
     * Gets an entity class name by its table name
     *
     * @param string $tableName
     * @param string $customEntityName The name of a custom entity
     * @return string|null
     * @throws \RuntimeException
     */
    protected function getEntityClassName($tableName, $customEntityName = null)
    {
        if (!isset($this->tableToEntityMap[$tableName])) {
            $entityClassName = !empty($customEntityName)
                ? $customEntityName
                : $this->entityClassResolver->getEntityClassByTableName($tableName);
            if (empty($entityClassName)) {
                throw new \RuntimeException(sprintf('Cannot find entity for "%s" table.', $tableName));
            }
            $this->tableToEntityMap[$tableName] = $entityClassName;
        }

        return $this->tableToEntityMap[$tableName];
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
        $fieldName = $this->entityClassResolver->getFieldNameByColumnName($tableName, $columnName);
        if (empty($fieldName)) {
            $fieldName = $columnName;
        }

        return $fieldName;
    }
}
