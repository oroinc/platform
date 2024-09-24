<?php

namespace Oro\Bundle\EntityBundle\ORM\TriggerDriver;

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
