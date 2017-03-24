<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\NavigationBundle\EventListener\AclCacheClearListener;

class AclCacheClearListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnCacheClear()
    {
        $cache = $this->createMock(CacheProvider::class);
        $cache->expects($this->once())
            ->method('deleteAll');
        $listener = new AclCacheClearListener($cache);
        $listener->onCacheClear();
    }
}
