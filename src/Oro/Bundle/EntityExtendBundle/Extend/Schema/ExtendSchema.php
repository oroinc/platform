<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;

class ExtendSchema extends Schema
{
    /**
     * @var ExtendOptionManager
     */
    protected $extendOptionManager;

    /**
     * @param ExtendOptionManager $extendOptionManager
     * @param Table[]             $tables
     * @param Sequence[]          $sequences
     * @param SchemaConfig        $schemaConfig
     */
    public function __construct(
        ExtendOptionManager $extendOptionManager,
        array $tables = [],
        array $sequences = [],
        SchemaConfig $schemaConfig = null
    ) {
        $this->extendOptionManager = $extendOptionManager;

        parent::__construct($tables, $sequences, $schemaConfig);
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
        parent::createTable($tableName);

        return $this->getTable($tableName);
    }

    /**
     * {@inheritdoc}
     */
    // @codingStandardsIgnoreStart
    protected function _addTable(Table $table)
    {
        if (!($table instanceof ExtendTable)) {
            $table = new ExtendTable($this->extendOptionManager, $table);
        }
        parent::_addTable($table);
    }
    // @codingStandardsIgnoreEnd
}
