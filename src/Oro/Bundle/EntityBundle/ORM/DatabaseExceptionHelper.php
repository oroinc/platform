<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;

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
     * @param DriverException $exception
     * @return bool
     */
    public function isDeadlock(DriverException $exception)
    {
        $code = (string)$exception->getErrorCode();
        $platform = $this->registry->getConnection()->getDatabasePlatform();

        if ($platform instanceof MySqlPlatform) {
            //Error: 1213 SQLSTATE: 40001 (ER_LOCK_DEADLOCK)
            return $code === '1213';
        } elseif ($platform instanceof PostgreSqlPlatform) {
            //40P01 DEADLOCK DETECTED deadlock_detected
            return $code === '40P01';
        }

        return false;
    }
}
