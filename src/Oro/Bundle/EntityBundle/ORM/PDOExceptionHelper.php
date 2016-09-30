<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;

class PDOExceptionHelper
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
        $isDeadlock = false;

        $code = $exception->getErrorCode();
        $platform = $this->registry->getConnection()->getDatabasePlatform();

        if ($platform instanceof MySqlPlatform && $code == '1213') {
            //Error: 1213 SQLSTATE: 40001 (ER_LOCK_DEADLOCK)
            $isDeadlock = true;
        } elseif ($platform instanceof PostgreSqlPlatform && $code == '40P01') {
            //40P01 DEADLOCK DETECTED deadlock_detected
            $isDeadlock = true;
        }

        return $isDeadlock;
    }
}
