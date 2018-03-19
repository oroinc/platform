<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL;

/**
 * Helper class to get information about database exceptions
 */
class DatabaseExceptionHelper
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \Exception $e
     * @return DBAL\Driver\DriverException|\Exception|null
     */
    public function getDriverException(\Exception $e)
    {
        $driverException = null;
        if ($e instanceof DBAL\Driver\DriverException) {
            $driverException = $e;
        } elseif ($e instanceof DBAL\Exception\DriverException) {
            $driverException = $e->getPrevious();
        }

        return $driverException;
    }

    /**
     * @param DBAL\Driver\DriverException $exception
     * @return bool
     */
    public function isDeadlock(DBAL\Driver\DriverException $exception)
    {
        $sqlState = (string)$exception->getSQLState();
        $platform = $this->registry->getConnection()->getDatabasePlatform();

        if ($platform instanceof DBAL\Platforms\MySqlPlatform) {
            $code = (string)$exception->getErrorCode();
            //SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock;
            //SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded
            return $sqlState === '40001' || $code === '1205' || $code === '1213';
        } elseif ($platform instanceof DBAL\Platforms\PostgreSqlPlatform) {
            //40P01 DEADLOCK DETECTED deadlock_detected
            return $sqlState === '40P01';
        }

        return false;
    }
}
