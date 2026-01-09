<?php

namespace Oro\Bundle\MigrationBundle\Migration\Schema;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

/**
 * Extends Schema to manage table creation with a custom name generator.
 */
class SchemaWithNameGenerator extends Schema
{
    public const TABLE_CLASS = 'Oro\Bundle\MigrationBundle\Migration\Schema\TableWithNameGenerator';

    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param DbIdentifierNameGenerator $nameGenerator
     * @param Table[]                   $tables
     * @param Sequence[]                $sequences
     * @param SchemaConfig|null $schemaConfig
     */
    public function __construct(
        DbIdentifierNameGenerator $nameGenerator,
        array $tables = [],
        array $sequences = [],
        ?SchemaConfig $schemaConfig = null
    ) {
        $this->nameGenerator = $nameGenerator;

        parent::__construct($tables, $sequences, $schemaConfig);
    }

    #[\Override]
    protected function createTableObject(array $args)
    {
        $args['nameGenerator'] = $this->nameGenerator;

        return parent::createTableObject($args);
    }

    #[\Override]
    public function renameTable($oldTableName, $newTableName)
    {
        throw new Exception(
            "Schema#renameTable() was removed, because it drops and recreates " .
            "the table instead. There is no fix available, because a schema diff cannot reliably detect if a " .
            "table was renamed or one table was created and another one dropped."
        );
    }
}
