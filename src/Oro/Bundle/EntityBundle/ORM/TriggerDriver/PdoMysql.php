<?php

namespace Oro\Bundle\EntityBundle\ORM\TriggerDriver;

class PdoMysql extends AbstractDriver
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::DRIVER_MYSQL;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSqlDisable()
    {
        return 'SET FOREIGN_KEY_CHECKS = 0';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSqlEnable()
    {
        return 'SET FOREIGN_KEY_CHECKS = 1';
    }
}
