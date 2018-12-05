<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\Cache;

use Oro\Bundle\AssetBundle\Cache\BundlesPathCache;
use Oro\Bundle\AssetBundle\Cache\BundlesPathCacheWarmer;
use PHPUnit\Framework\TestCase;

class BundlesPathCacheWarmerTest extends TestCase
{
    /**
     * @var BundlesPathCache
     */
    private $cache;

    /**
     * @var BundlesPathCacheWarmer
     */
    private $warmer;

    protected function setUp()
    {
        $this->cache = $this->createMock(BundlesPathCache::class);
        $this->warmer = new BundlesPathCacheWarmer($this->cache);
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
