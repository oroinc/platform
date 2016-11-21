<?php

namespace Oro\Bundle\EntityBundle\ORM;

interface DatabaseDriverInterface
{
    const DRIVER_POSTGRESQL = 'pdo_pgsql';
    const DRIVER_MYSQL      = 'pdo_mysql';

    /**
     * @return string
     */
    public function getName();
}
