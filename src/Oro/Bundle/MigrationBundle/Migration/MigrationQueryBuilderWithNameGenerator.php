<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Oro\Bundle\MigrationBundle\Exception\InvalidNameException;
use Oro\Bundle\MigrationBundle\Migration\Schema\SchemaWithNameGenerator;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class MigrationQueryBuilderWithNameGenerator extends MigrationQueryBuilder
{
    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param DbIdentifierNameGenerator $nameGenerator
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchemaObject($tables, $sequences, $schemaConfig)
    {
        return new SchemaWithNameGenerator(
            $this->nameGenerator,
            $tables,
            $sequences,
            $schemaConfig
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function checkTableName($tableName, Migration $migration)
    {
        parent::checkTableName($tableName, $migration);
        if (strlen($tableName) > $this->nameGenerator->getMaxIdentifierSize()) {
            throw new InvalidNameException(
                sprintf(
                    'Max table name length is %s. Please correct "%s" table in "%s" migration',
                    $this->nameGenerator->getMaxIdentifierSize(),
                    $tableName,
                    get_class($migration)
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkColumnName($tableName, $columnName, Migration $migration)
    {
        parent::checkColumnName($tableName, $columnName, $migration);
        if (strlen($columnName) > $this->nameGenerator->getMaxIdentifierSize()) {
            throw new InvalidNameException(
                sprintf(
                    'Max column name length is %s. Please correct "%s:%s" column in "%s" migration',
                    $this->nameGenerator->getMaxIdentifierSize(),
                    $tableName,
                    $columnName,
                    get_class($migration)
                )
            );
        }
    }
}
