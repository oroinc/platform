<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\MappingException;

class MigrationQueryBuilder
{
    const MAX_TABLE_NAME_LENGTH = 50;

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

            $result[get_class($migration)] = $queries;
            $fromSchema = $toSchema;
        }

        return $result;
    }
}
