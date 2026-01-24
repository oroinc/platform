<?php

namespace Oro\Bundle\EntityBundle\ORM\TriggerDriver;

/**
 * PostgreSQL trigger driver for enabling and disabling table triggers.
 *
 * This driver implements trigger management for PostgreSQL by using the
 * `ALTER TABLE DISABLE/ENABLE TRIGGER ALL` commands to control all triggers on a specific table.
 */
class PdoPgsql extends AbstractDriver
{
    #[\Override]
    public function getName()
    {
        return self::DRIVER_POSTGRESQL;
    }

    #[\Override]
    protected function getSqlDisable()
    {
        return 'ALTER TABLE %s DISABLE TRIGGER ALL';
    }

    #[\Override]
    protected function getSqlEnable()
    {
        return 'ALTER TABLE %s ENABLE TRIGGER ALL';
    }
}
