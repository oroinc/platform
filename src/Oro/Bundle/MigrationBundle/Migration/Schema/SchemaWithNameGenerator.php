<?php

namespace Oro\Bundle\MigrationBundle\Migration\Schema;

use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class SchemaWithNameGenerator extends Schema
{
    const TABLE_CLASS = 'Oro\Bundle\MigrationBundle\Migration\Schema\TableWithNameGenerator';

    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param DbIdentifierNameGenerator $nameGenerator
     * @param Table[]                   $tables
     * @param Sequence[]                $sequences
     * @param SchemaConfig              $schemaConfig
     */
    public function __construct(
        DbIdentifierNameGenerator $nameGenerator,
        array $tables = [],
        array $sequences = [],
        SchemaConfig $schemaConfig = null
    ) {
        $this->nameGenerator = $nameGenerator;

        parent::__construct($tables, $sequences, $schemaConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function createTableObject(array $args)
    {
        $args['nameGenerator'] = $this->nameGenerator;

        return parent::createTableObject($args);
    }
}
