<?php

namespace Oro\Bundle\EntityBundle\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;

/**
 * Provides a set of "safe" functions to check the data access layer state, where "safe" means that these functions
 * never throw certain DB-related exceptions and can be used even when the data access layer is not configured properly.
 */
class SafeDatabaseChecker
{
    /**
     * Checks whether a database connection can be established and all given tables exist in the database.
     * \PDOException and \Doctrine\DBAL\DBALException exceptions are silently discarded.
     *
     * @param Connection $connection
     * @param string[]|string|null $tables
     *
     * @return bool
     */
    public static function tablesExist(Connection $connection, $tables)
    {
        return self::safeDatabaseCallable(function () use ($connection, $tables) {
            if (!$tables) {
                return false;
            }

            $connection->connect();
            return $connection->getSchemaManager()->tablesExist($tables);
        }, false);
    }

    /**
     * Returns the table name for a given entity. \PDOException, \Doctrine\DBAL\DBALException,
     * \Doctrine\ORM\ORMException and \ReflectionException exceptions are silently discarded.
     *
     * @param ManagerRegistry $doctrine
     * @param string $entityName
     *
     * @return string|null the table name or NULL if it cannot be determined
     */
    public static function getTableName(ManagerRegistry $doctrine, $entityName)
    {
        return self::safeDatabaseExtendCallable(function () use ($doctrine, $entityName) {
            if (!empty($entityName)) {
                $em = $doctrine->getManagerForClass($entityName);
                if ($em instanceof EntityManagerInterface) {
                    return $em->getClassMetadata($entityName)->getTableName();
                }
            }

            return null;
        });
    }

    /**
     * Returns metadata of all entities registered in a given entity manager. \PDOException,
     * \Doctrine\DBAL\DBALException, \Doctrine\ORM\ORMException and \ReflectionException exceptions
     * are silently discarded.
     *
     * @param ObjectManager $manager
     *
     * @return ClassMetadata[]
     */
    public static function getAllMetadata(ObjectManager $manager)
    {
        return self::safeDatabaseExtendCallable(function () use ($manager) {
            return $manager->getMetadataFactory()->getAllMetadata();
        }, []);
    }

    /**
     * Executes a given callable while silently discarding \PDOException and \Doctrine\DBAL\DBALException exceptions.
     *
     * @param callable $callable
     * @param null $emptyValue
     * @return mixed|null
     */
    public static function safeDatabaseCallable(callable $callable, $emptyValue = null)
    {
        try {
            return call_user_func($callable);
        } catch (\PDOException $e) {
        } catch (DBALException $e) {
        }

        return $emptyValue;
    }

    /**
     * Executes a given callable while silently discarding \PDOException, \Doctrine\DBAL\DBALException,
     * \Doctrine\ORM\ORMException and \ReflectionException exceptions.
     *
     * @param callable $callable
     * @param null $emptyValue
     * @return mixed|null
     */
    public static function safeDatabaseExtendCallable(callable $callable, $emptyValue = null)
    {
        try {
            return call_user_func($callable);
        } catch (\PDOException $e) {
        } catch (DBALException $e) {
        } catch (ORMException $e) {
        } catch (\ReflectionException $e) {
        }

        return $emptyValue;
    }
}
