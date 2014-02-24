<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\MappingException;

class MigrationQueryBuilder
{
    const MAX_TABLE_NAME_LENGTH = 30;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param Migration[] $migrations
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @return array
     *   key - class name of migration file
     *   value - array of sql queries from this file
     */
    public function getQueries(array $migrations)
    {
        $result = [];

        $connection = $this->em->getConnection();
        $sm         = $connection->getSchemaManager();
        $platform   = $connection->getDatabasePlatform();
        $fromSchema = $sm->createSchema();
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

            $result[get_class($migration)] = $queries;
            $fromSchema = $toSchema;
        }

        return $result;
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
