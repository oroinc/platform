<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Table;

class ExtendSchema extends Schema
{
    /**
     * @var ExtendOptionManager
     */
    protected $extendOptionManager;

    /**
     * @param ExtendOptionManager $extendOptionManager
     * @param array               $tables
     * @param array               $sequences
     * @param SchemaConfig        $schemaConfig
     */
    public function __construct(
        ExtendOptionManager $extendOptionManager,
        array $tables = [],
        array $sequences = [],
        SchemaConfig $schemaConfig = null
    ) {
        $this->extendOptionManager = $extendOptionManager;

        $extendTables = [];
        foreach ($tables as $table) {
            $extendTables[] = new ExtendTable($this, $this->extendOptionManager, $table);
        }

        parent::__construct($extendTables, $sequences, $schemaConfig);
    }

    /**
     * @return array
     */
    public function getExtendOptions()
    {
        return $this->extendOptionManager->getExtendOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function createTable($tableName)
    {
        $baseTable = new Table($tableName);
        $table = new ExtendTable($this, $this->extendOptionManager, $baseTable);

        $this->_addTable($table);

        foreach ($this->_schemaConfig->getDefaultTableOptions() as $name => $value) {
            $table->addOption($name, $value);
        }

        return $table;
    }
}
