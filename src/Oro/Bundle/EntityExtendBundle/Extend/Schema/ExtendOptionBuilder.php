<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

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
        $this->result[$entityClassName]['fields'][$columnName] = [
            'type'    => $columnType,
            'configs' => $options
        ];
    }

    public function get()
    {
        return $this->result;
    }

    protected function getEntityClassName($tableName)
    {
        if (!isset($this->tableToEntityMap[$tableName])) {
            $entityClassName = $this->entityClassResolver->getEntityClassByTableName($tableName);
            if (empty($entityClassName)) {
                throw new \RuntimeException(sprintf('Cannot find entity for "%s" table.', $tableName));
            }
            $this->tableToEntityMap[$tableName] = $entityClassName;
        }

        return $this->tableToEntityMap[$tableName];
    }
}
