<?php

namespace Oro\Bundle\EntityBundle\ORM\TriggerDriver;

class PdoPgsql extends AbstractDriver
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::DRIVER_POSTGRESQL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSqlDisable()
    {
        return 'ALTER TABLE %s DISABLE TRIGGER ALL';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSqlEnable()
    {
        return 'ALTER TABLE %s ENABLE TRIGGER ALL';
    }
}
