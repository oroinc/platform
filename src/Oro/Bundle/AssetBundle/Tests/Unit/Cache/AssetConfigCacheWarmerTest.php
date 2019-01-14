<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Cache;

use Oro\Bundle\AssetBundle\Cache\AssetConfigCache;
use Oro\Bundle\AssetBundle\Cache\AssetConfigCacheWarmer;
use PHPUnit\Framework\TestCase;

class AssetConfigCacheWarmerTest extends TestCase
{
    /**
     * @var AssetConfigCache
     */
    private $cache;

    /**
     * @var AssetConfigCacheWarmer
     */
    private $warmer;

    protected function setUp()
    {
        $this->cache = $this->createMock(AssetConfigCache::class);
        $this->warmer = new AssetConfigCacheWarmer($this->cache);
    }

    public function testIsOptional()
    {
        $this->assertFalse($this->warmer->isOptional());
    }

    public function testWarmUp()
    {
        $this->cache->expects($this->once())
            ->method('warmup');
        $this->warmer->warmUp('cache/dir');
    }
}
