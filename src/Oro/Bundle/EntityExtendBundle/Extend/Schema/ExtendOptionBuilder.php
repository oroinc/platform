<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendOptionBuilder
{
    /**
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    protected $tableToEntityMap = [];

    protected $result = [];

    public function __construct(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
    }

    public function addTableOptions($tableName, $options)
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

    public function addColumnOptions($tableName, $columnName, $columnType, $options)
    {
        $entityClassName = $this->getEntityClassName($tableName);

        if (!isset($this->result[$entityClassName])) {
            $this->result[$entityClassName] = [];
        }
        if (!isset($this->result[$entityClassName]['fields'])) {
            $this->result[$entityClassName]['fields'] = [];
        }

        if (in_array($columnType, ['oneToMany', 'manyToOne', 'manyToMany'])
            && isset($options['extend'])
            && isset($options['extend']['target'])
        ) {

            if ((
                    $columnType == 'manyToOne'
                    && (
                        empty($options['extend']['target']['table_name'])
                        || empty($options['extend']['target']['column'])
                    )
                )
                || (
                    in_array($columnType, ['oneToMany', 'manyToMany'])
                    && (
                        empty($options['extend']['target']['table_name'])
                        || empty($options['extend']['target']['title_columns'])
                        || empty($options['extend']['target']['grid_columns'])
                        || empty($options['extend']['target']['detailed_columns'])
                    )
                )
            ) {
                throw new \RuntimeException(
                    sprintf('Configuration error for table "%s" column "%s"', $tableName, $columnName)
                );
            }

            foreach ($options['extend']['target'] as $optionName => $optionValue) {
                switch ($optionName) {
                    case 'table_name':
                        $options['extend']['target_entity'] =
                            $this->entityClassResolver->getEntityClassByTableName($optionValue);
                        break;
                    case 'column':
                        $options['extend']['target_field'] = $this->entityClassResolver->getFieldNameByColumnName(
                            $options['extend']['target']['table_name'],
                            $optionValue
                        );
                        break;
                    case 'title_columns':
                        $values = [];
                        foreach ($optionValue as $value) {
                            $values[] = $this->entityClassResolver->getFieldNameByColumnName(
                                $options['extend']['target']['table_name'],
                                $value
                            );
                        }
                        $options['extend']['target_title'] = $values;
                        break;
                    case 'grid_columns':
                        $values = [];
                        foreach ($optionValue as $value) {
                            $values[] = $this->entityClassResolver->getFieldNameByColumnName(
                                $options['extend']['target']['table_name'],
                                $value
                            );
                        }
                        $options['extend']['target_grid'] = $values;
                        break;
                    case 'detailed_columns':
                        $values = [];
                        foreach ($optionValue as $value) {
                            $values[] = $this->entityClassResolver->getFieldNameByColumnName(
                                $options['extend']['target']['table_name'],
                                $value
                            );
                        }
                        $options['extend']['target_detailed'] = $values;
                        break;
                }
            }

            $options['extend']['relation_key'] = ExtendHelper::buildRelationKey(
                $entityClassName,
                $columnName,
                $columnType,
                $options['extend']['target_entity']
            );

            unset($options['extend']['target']);
        }

        $this->result[$entityClassName]['fields'][$columnName] = [
            'type'    => $columnType,
            'configs' => $options
        ];
    }

    /**
     * Returns extend options
     *
     * @return array
     */
    public function get()
    {
        return $this->result;
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
}
