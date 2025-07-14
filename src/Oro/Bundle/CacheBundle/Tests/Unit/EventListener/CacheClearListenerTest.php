<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\EventListener;

use Oro\Bundle\CacheBundle\EventListener\CacheClearListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class CacheClearListenerTest extends TestCase
{
    private AbstractAdapter&MockObject $cache;
    private CacheClearListener $listener;

    #[\Override]
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
