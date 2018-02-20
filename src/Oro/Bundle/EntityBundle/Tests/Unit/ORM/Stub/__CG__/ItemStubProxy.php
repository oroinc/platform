<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__;

use Doctrine\Common\Persistence\Proxy;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;

// @codingStandardsIgnoreStart
class ItemStubProxy extends ItemStub implements Proxy
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
