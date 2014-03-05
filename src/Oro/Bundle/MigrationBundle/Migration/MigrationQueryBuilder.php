<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Exception\InvalidNameException;

class MigrationQueryBuilder
{
    const MAX_TABLE_NAME_LENGTH = 30;

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
        $fromSchema = $this->getSchema();
        $queryBag   = new QueryBag();
        foreach ($migrations as $migration) {
            $toSchema = clone $fromSchema;

            $this->setMigrationHelpers($migration);
            $migration->up($toSchema, $queryBag);

            $comparator = new Comparator();
            $schemaDiff = $comparator->compare($fromSchema, $toSchema);

            $this->checkTableNameLengths($schemaDiff->newTables, $migration);

            $changedTables = $schemaDiff->changedTables;
            foreach ($changedTables as $tableName => $diff) {
                $this->checkColumnsNameLength(
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
     * Gets a database schema
     *
     * @return Schema
     */
    protected function getSchema()
    {
        return $this->connection->getSchemaManager()->createSchema();
    }

    /**
     * Sets helpers to the given migration
     *
     * @param Migration $migration
     */
    protected function setMigrationHelpers(Migration $migration)
    {
    }

    /**
     * @param Table[]   $tables
     * @param Migration $migration
     * @throws InvalidNameException
     */
    protected function checkTableNameLengths($tables, Migration $migration)
    {
        foreach ($tables as $table) {
            if (strlen($table->getName()) > self::MAX_TABLE_NAME_LENGTH) {
                throw new InvalidNameException(
                    sprintf(
                        'Max table name length is %s. Please correct "%s" table in "%s" migration',
                        self::MAX_TABLE_NAME_LENGTH,
                        $table->getName(),
                        get_class($migration)
                    )
                );
            }

            $this->checkColumnsNameLength($table->getName(), $table->getColumns(), $migration);
        }
    }

    /**
     * @param string    $tableName
     * @param Column[]  $columns
     * @param Migration $migration
     * @throws InvalidNameException
     */
    protected function checkColumnsNameLength($tableName, $columns, Migration $migration)
    {
        foreach ($columns as $column) {
            if (strlen($column->getName()) > self::MAX_TABLE_NAME_LENGTH) {
                throw new InvalidNameException(
                    sprintf(
                        'Max column name length is %s. Please correct "%s:%s" column in "%s" migration',
                        self::MAX_TABLE_NAME_LENGTH,
                        $tableName,
                        $column->getName(),
                        get_class($migration)
                    )
                );
            }
        }
    }
}
