<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Cache;

use Oro\Bundle\AssetBundle\Cache\AssetConfigCache;
use Oro\Bundle\AssetBundle\Cache\AssetConfigCacheWarmer;
use PHPUnit\Framework\TestCase;

class AssetConfigCacheWarmerTest extends TestCase
{
    private AssetConfigCache $cache;
    private AssetConfigCacheWarmer $warmer;

    #[\Override]
    protected function setUp(): void
    {
        $this->cache = $this->createMock(AssetConfigCache::class);
        $this->warmer = new AssetConfigCacheWarmer($this->cache);
    }

    public function testIsOptional(): void
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    public function testWarmUp(): void
    {
        $this->cache->expects($this->once())
            ->method('warmup');
        $this->warmer->warmUp('cache/dir');
    }
}
