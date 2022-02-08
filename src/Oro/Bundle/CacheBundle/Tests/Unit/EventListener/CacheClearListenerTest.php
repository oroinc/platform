<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\EventListener;

use Oro\Bundle\CacheBundle\EventListener\CacheClearListener;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class CacheClearListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CacheClearListener */
    private $listener;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(AbstractAdapter::class);

        $this->listener = new CacheClearListener($this->cache);
    }

    public function testClearCache(): void
    {
        $this->cache->expects($this->once())
            ->method('clear');

        $this->listener->clearCache();
    }
}
