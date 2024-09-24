<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\__CG__;

use Doctrine\Persistence\Proxy;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;

// @codingStandardsIgnoreStart
class ItemStubProxy extends ItemStub implements Proxy
{
    #[\Override]
    public function __isInitialized()
    {
        return false;
    }

    #[\Override]
    public function __load()
    {
    }
}
// @codingStandardsIgnoreEnd
