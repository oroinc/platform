<?php

namespace Oro\Bundle\EntityBundle\ORM;

/**
 * Interface for Database Drivers
 */
interface DatabaseDriverInterface
{
    const DRIVER_POSTGRESQL = 'postgresql';
    const DRIVER_MYSQL      = 'mysql';

    /**
     * @return string
     */
    public function getName();
}
