<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\ORM\Mapping\MappingException;

class MigrationQueryBuilder
{
    const MAX_TABLE_NAME_LENGTH = 50;

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

        $sm         = $this->connection->getSchemaManager();
        $platform   = $this->connection->getDatabasePlatform();
        $fromSchema = $sm->createSchema();
        foreach ($migrations as $migration) {
            $toSchema   = clone $fromSchema;
            $queries    = $migration->up($toSchema);
            $comparator = new Comparator();
            $schemaDiff = $comparator->compare($fromSchema, $toSchema);
            foreach ($schemaDiff->newTables as $newTable) {
                if (strlen($newTable->getName()) > self::MAX_TABLE_NAME_LENGTH) {
                    throw new MappingException(
                        sprintf(
                            'Max table name length is %s. Please correct "%s" table in "%s" migration',
                            self::MAX_TABLE_NAME_LENGTH,
                            $newTable->getName(),
                            get_class($migration)
                        )
                    );
                }
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
}
