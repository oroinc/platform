<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
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
        if (isset($options['extend']['entity_name'])) {
            $entityName = $options['extend']['entity_name'];
            unset($options['extend']['entity_name']);
        }
        $entityClassName = $this->getEntityClassName($tableName, $entityName);
        if (!isset($this->result[$entityClassName])) {
            $this->result[$entityClassName] = [];
        }
        $this->result[$entityClassName]['configs'] = $options;
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $columnType
     * @param array  $options
     */
    public function addColumnOptions($tableName, $columnName, $columnType, $options)
    {
        $entityClassName = $this->getEntityClassName($tableName);

        if (!isset($this->result[$entityClassName])) {
            $this->result[$entityClassName] = [];
        }
        if (!isset($this->result[$entityClassName]['fields'])) {
            $this->result[$entityClassName]['fields'] = [];
        }

        if (in_array($columnType, ['oneToMany', 'manyToOne', 'manyToMany'])) {
            if (!isset($options['extend'])) {
                $options['extend'] = [];
            }
            foreach ($options['_target'] as $optionName => $optionValue) {
                switch ($optionName) {
                    case 'table_name':
                        $options['extend']['target_entity'] = $this->getEntityClassName($optionValue);
                        break;
                    case 'column':
                        $options['extend']['target_field'] =
                            $this->getFieldName($options['_target']['table_name'], $optionValue);
                        break;
                    case 'columns':
                        foreach ($optionValue as $group => $columns) {
                            $values = [];
                            foreach ($columns as $column) {
                                $values[] = $this->getFieldName($options['_target']['table_name'], $column);
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

            unset($options['_target']);
        }

        $this->result[$entityClassName]['fields'][$columnName] = [
            'type'    => $columnType,
            'configs' => $options
        ];
    }

    /**
     * Gets an entity class name by its table name
     *
     * @param string $tableName
     * @param string $customEntityName The name of custom entity
     * @return string|null
     * @throws \RuntimeException
     */
    protected function getEntityClassName($tableName, $customEntityName = null)
    {
        if (!isset($this->tableToEntityMap[$tableName])) {
            $entityClassName = $this->entityClassResolver->getEntityClassByTableName($tableName);
            if (empty($entityClassName)) {
                if (empty($customEntityName)) {
                    throw new \RuntimeException(sprintf('Cannot find entity for "%s" table.', $tableName));
                }
                if (!preg_match('/^[A-Z][a-zA-Z\d]+$/', $customEntityName)) {
                    throw new \RuntimeException(sprintf('Invalid entity name: "%s".', $customEntityName));
                }

                $entityClassName = ExtendConfigDumper::ENTITY . $customEntityName;
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
