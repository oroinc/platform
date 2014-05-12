<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__;

use Doctrine\Common\Persistence\Proxy;

// @codingStandardsIgnoreStart
class ItemStubProxy implements Proxy
{
    public function __isInitialized()
    {
        return false;
    }

    public function __load()
    {
    }
}
// @codingStandardsIgnoreEnd
