<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture\__CG__;

use Doctrine\Common\Persistence\Proxy;

use Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture\LoggableClass as BaseLoggableClas;

class LoggableClass extends BaseLoggableClas implements Proxy
{
    public function __load()
    {
    }

    public function __isInitialized()
    {
        return false;
    }
}
