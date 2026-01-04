<?php

namespace Oro\Bundle\EntityBundle\ORM;

/**
 * Interface for Database Drivers
 */
interface DatabaseDriverInterface
{
    public const DRIVER_POSTGRESQL = 'postgresql';
    public const DRIVER_MYSQL      = 'mysql';

    /**
     * @return string
     */
    public function getName();
}
