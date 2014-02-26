<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\MappingException;

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
     * @param Migration[] $migrations
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @return array
     *   'migration' => class name of migration file
     *   'queries'   => array of sql queries from this file
     */
    public function getQueries(array $migrations)
    {
        $result = [];

        $platform   = $this->connection->getDatabasePlatform();
        $fromSchema = $this->getSchema();
        foreach ($migrations as $migration) {
            $toSchema   = clone $fromSchema;
            $queries    = $migration->up($toSchema);
            $comparator = new Comparator();
            $schemaDiff = $comparator->compare($fromSchema, $toSchema);

            $this->checkTableNameLengths($schemaDiff->newTables, $migration);

            /** @var \Doctrine\DBAL\Schema\TableDiff $changedTables */
            $changedTables = $schemaDiff->changedTables;
            foreach ($changedTables as $tableName => $diff) {
                $this->checkColumnsNameLength(
                    $tableName,
                    array_values($diff->addedColumns),
                    $migration
                );
            }

            $queries = array_merge(
                $schemaDiff->toSql($platform),
                $queries
            );

            $result[]   = [
                'migration' => get_class($migration),
                'queries'   => $queries
            ];
            $fromSchema = $toSchema;
        }

        return $result;
    }

    /**
     * @return Schema
     */
    protected function getSchema()
    {
        return $this->connection->getSchemaManager()->createSchema();
    }

    /**
     * @param \Doctrine\DBAL\Schema\Table[] $tables
     * @param Migration $migration
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function checkTableNameLengths($tables, Migration $migration)
    {
        foreach ($tables as $table) {
            if (strlen($table->getName()) > self::MAX_TABLE_NAME_LENGTH) {
                throw new MappingException(
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
     * @param string $tableName
     * @param \Doctrine\DBAL\Schema\Column[] $columns
     * @param Migration $migration
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function checkColumnsNameLength($tableName, $columns, Migration $migration)
    {
        foreach ($columns as $column) {
            if (strlen($column->getName()) > self::MAX_TABLE_NAME_LENGTH) {
                throw new MappingException(
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
