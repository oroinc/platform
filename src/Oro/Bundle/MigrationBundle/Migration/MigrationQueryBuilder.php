<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Exception\InvalidNameException;

class MigrationQueryBuilder
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Gets a connection object this migration query builder works with
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Gets a list of SQL queries can be used to apply database changes
     *
     * @param Migration[] $migrations
     * @return array
     *   'migration' => class name of a migration
     *   'queries'   => a list of sql queries (a query can be a string or instance of MigrationQuery)
     * @throws InvalidNameException if invalid table or column name is detected
     */
    public function getQueries(array $migrations)
    {
        $result = [];

        $platform   = $this->connection->getDatabasePlatform();
        $sm         = $this->connection->getSchemaManager();
        $fromSchema = $this->createSchemaObject(
            $sm->listTables(),
            $platform->supportsSequences() ? $sm->listSequences() : [],
            $sm->createSchemaConfig()
        );
        $queryBag   = new QueryBag();
        foreach ($migrations as $migration) {
            $toSchema = clone $fromSchema;

            $this->setExtensions($migration);
            $migration->up($toSchema, $queryBag);

            $comparator = new Comparator();
            $schemaDiff = $comparator->compare($fromSchema, $toSchema);

            $this->checkTables($schemaDiff->newTables, $migration);
            $changedTables = $schemaDiff->changedTables;
            foreach ($changedTables as $tableName => $diff) {
                $this->checkColumnNames(
                    $tableName,
                    array_values($diff->addedColumns),
                    $migration
                );
            }

            $queries = array_merge(
                $queryBag->getPreSqls(),
                $schemaDiff->toSql($platform),
                $queryBag->getPostSqls()
            );

            $result[] = [
                'migration' => get_class($migration),
                'queries'   => $queries
            ];

            $fromSchema = $toSchema;
            $queryBag->clear();
        }

        return $result;
    }

    /**
     * Creates a database schema object
     *
     * @param Table[]      $tables
     * @param Sequence[]   $sequences
     * @param SchemaConfig $schemaConfig
     * @return Schema
     */
    public function createSchemaObject($tables, $sequences, $schemaConfig)
    {
        return new Schema($tables, $sequences, $schemaConfig);
    }

    /**
     * Sets extensions for the given migration
     *
     * @param Migration $migration
     */
    protected function setExtensions(Migration $migration)
    {
    }

    /**
     * Validates the given tables
     *
     * @param Table[]   $tables
     * @param Migration $migration
     * @throws InvalidNameException if invalid table or column name is detected
     */
    protected function checkTables($tables, Migration $migration)
    {
        foreach ($tables as $table) {
            $this->checkTableName($table->getName(), $migration);
            $this->checkColumnNames($table->getName(), $table->getColumns(), $migration);
        }
    }

    /**
     * Validates the given columns
     *
     * @param string    $tableName
     * @param Column[]  $columns
     * @param Migration $migration
     * @throws InvalidNameException if invalid column name is detected
     */
    protected function checkColumnNames($tableName, $columns, Migration $migration)
    {
        foreach ($columns as $column) {
            $this->checkColumnName($tableName, $column->getName(), $migration);
        }
    }

    /**
     * Validates table name
     *
     * @param string    $tableName
     * @param Migration $migration
     * @throws InvalidNameException if table name is invalid
     */
    protected function checkTableName($tableName, Migration $migration)
    {
    }

    /**
     * Validates column name
     *
     * @param string    $tableName
     * @param string    $columnName
     * @param Migration $migration
     * @throws InvalidNameException if column name is invalid
     */
    protected function checkColumnName($tableName, $columnName, Migration $migration)
    {
    }
}
