<?php

namespace Oro\Bundle\EntityBundle\ORM\Driver;

class PdoMysql extends AbstractDriver
{
    /**
     * @var string
     */
    protected $sql_disable = 'SET FOREIGN_KEY_CHECKS = 0';

    /**
     * @var string
     */
    protected $sql_enable  = 'SET FOREIGN_KEY_CHECKS = 1';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::DRIVER_MYSQL;
    }
}
