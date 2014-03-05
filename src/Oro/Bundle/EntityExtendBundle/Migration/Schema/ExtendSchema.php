<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsProviderInterface;

class ExtendSchema extends Schema
{
    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @param ExtendOptionsManager $extendOptionsManager
     * @param Table[]              $tables
     * @param Sequence[]           $sequences
     * @param SchemaConfig         $schemaConfig
     */
    public function __construct(
        ExtendOptionsManager $extendOptionsManager,
        array $tables = [],
        array $sequences = [],
        SchemaConfig $schemaConfig = null
    ) {
        $this->extendOptionsManager = $extendOptionsManager;

        parent::__construct($tables, $sequences, $schemaConfig);
    }

    /**
     * @return ExtendOptionsProviderInterface
     */
    public function getExtendOptionsProvider()
    {
        return $this->extendOptionsManager->getExtendOptionsProvider();
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
            $table = new ExtendTable($this->extendOptionsManager, $table);
        }
        parent::_addTable($table);
    }
    // @codingStandardsIgnoreEnd
}
