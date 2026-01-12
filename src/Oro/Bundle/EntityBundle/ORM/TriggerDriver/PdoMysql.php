<?php

namespace Oro\Bundle\EntityBundle\ORM\TriggerDriver;

/**
 * MySQL trigger driver for enabling and disabling foreign key checks.
 *
 * This driver implements trigger management for MySQL by controlling the `FOREIGN_KEY_CHECKS` setting,
 * which effectively disables and enables foreign key constraint enforcement.
 */
class PdoMysql extends AbstractDriver
{
    #[\Override]
    public function getName()
    {
        return self::DRIVER_MYSQL;
    }

    #[\Override]
    protected function getSqlDisable()
    {
        return 'SET FOREIGN_KEY_CHECKS = 0';
    }

    #[\Override]
    protected function getSqlEnable()
    {
        return 'SET FOREIGN_KEY_CHECKS = 1';
    }
}
