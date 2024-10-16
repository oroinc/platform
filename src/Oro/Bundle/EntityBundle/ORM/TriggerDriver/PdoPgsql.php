<?php

namespace Oro\Bundle\EntityBundle\ORM\TriggerDriver;

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
