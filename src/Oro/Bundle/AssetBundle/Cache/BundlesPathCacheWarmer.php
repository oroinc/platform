<?php

namespace Oro\Bundle\AssetBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Cache Warmer for BundlesPathCache.
 */
class BundlesPathCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var BundlesPathCache
     */
    private $cache;

    public function __construct(BundlesPathCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->cache->warmUp($cacheDir);
    }
}
