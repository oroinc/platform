<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CacheBundle\EventListener\CacheClearListener;

class CacheClearListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CacheClearListener */
    private $listener;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheProvider::class);

        $this->listener = new CacheClearListener($this->cache);
    }

    public function testClearCache(): void
    {
        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->listener->clearCache();
    }
}
