<?php

namespace Oro\Bundle\EntityBundle\ORM\Driver;

class PdoPgsql extends AbstractDriver
{
    /**
     * @var string
     */
    protected $sql_disable = 'ALTER TABLE %s DISABLE TRIGGER ALL';

    /**
     * @var string
     */
    protected $sql_enable = 'ALTER TABLE %s ENABLE TRIGGER ALL';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::DRIVER_POSTGRESQL;
    }
}
