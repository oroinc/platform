<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Schema;

use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Schema\SchemaWithNameGenerator;

class ExtendSchema extends SchemaWithNameGenerator
{
    const TABLE_CLASS = 'Oro\Bundle\EntityExtendBundle\Migration\Schema\ExtendTable';

    /**
     * @var ExtendOptionsManager
     */
    protected $extendOptionsManager;

    /**
     * @param ExtendOptionsManager            $extendOptionsManager
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     * @param Table[]                         $tables
     * @param Sequence[]                      $sequences
     * @param SchemaConfig                    $schemaConfig
     */
    public function __construct(
        ExtendOptionsManager $extendOptionsManager,
        ExtendDbIdentifierNameGenerator $nameGenerator,
        array $tables = [],
        array $sequences = [],
        SchemaConfig $schemaConfig = null
    ) {
        $this->extendOptionsManager = $extendOptionsManager;

        parent::__construct(
            $nameGenerator,
            $tables,
            $sequences,
            $schemaConfig
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createTableObject(array $args)
    {
        $args['extendOptionsManager'] = $this->extendOptionsManager;
        $args['schema']               = $this;

        return parent::createTableObject($args);
    }

    /**
     * @return array
     */
    public function getExtendOptions()
    {
        return $this->extendOptionsManager->getExtendOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        parent::__clone();

        /** @var ExtendTable $table */
        foreach ($this->_tables as $table) {
            $table->setSchema($this);
        }
    }
}
