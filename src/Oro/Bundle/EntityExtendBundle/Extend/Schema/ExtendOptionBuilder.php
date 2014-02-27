<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

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
        if (isset($options['entity']['name'])) {
            $entityName = $options['entity']['name'];
            unset($options['entity']['name']);
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

        var_dump($entityClassName);

        if (!isset($this->result[$entityClassName])) {
            $this->result[$entityClassName] = [];
        }
        if (!isset($this->result[$entityClassName]['fields'])) {
            $this->result[$entityClassName]['fields'] = [];
        }
        $this->result[$entityClassName]['fields'][$columnName] = [
            'type'    => $columnType,
            'configs' => $options
        ];

        var_dump($this->result);
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
