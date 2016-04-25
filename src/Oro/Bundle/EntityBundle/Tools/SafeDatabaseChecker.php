<?php

namespace Oro\Bundle\EntityBundle\Tools;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

/**
 * Provides a set of functions that can be used to do a safe-check a state of the data access layer.
 * "safe" means that these functions never throw exceptions
 * and they can be used even if the data access layer is not configured properly.
 */
class SafeDatabaseChecker
{
    /**
     * Checks whether a database connection can be established and all given tables exist in the database.
     *
     * @param Connection           $connection
     * @param string[]|string|null $tables
     *
     * @return bool
     */
    public static function tablesExist(Connection $connection, $tables)
    {
        $result = false;
        if (!empty($tables)) {
            try {
                $connection->connect();
                $result = $connection->getSchemaManager()->tablesExist($tables);
            } catch (\PDOException $e) {
            } catch (DBALException $e) {
            }
        }

        return $result;
    }

    /**
     * Returns the table name for a given entity.
     *
     * @param ManagerRegistry $doctrine
     * @param string          $entityName
     *
     * @return string|null the table name or NULL if it cannot be determined
     */
    public static function getTableName(ManagerRegistry $doctrine, $entityName)
    {
        $result = null;

        if (!empty($entityName)) {
            try {
                $em = $doctrine->getManagerForClass($entityName);
                if ($em instanceof EntityManagerInterface) {
                    $result = $em->getClassMetadata($entityName)->getTableName();
                }
            } catch (\PDOException $e) {
            } catch (DBALException $e) {
            } catch (ORMException $e) {
            } catch (\ReflectionException $e) {
            }
        }

        return $result;
    }

    /**
     * Returns metadata of all entities registered in a given entity manager.
     *
     * @param ObjectManager $manager
     *
     * @return ClassMetadata[]
     */
    public static function getAllMetadata(ObjectManager $manager)
    {
        $allMetadata = [];
        try {
            $allMetadata = $manager->getMetadataFactory()->getAllMetadata();
        } catch (\PDOException $e) {
        } catch (DBALException $e) {
        } catch (ORMException $e) {
        } catch (\ReflectionException $e) {
        }

        return $allMetadata;
    }
}
