<?php

namespace Oro\Bundle\AssetBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Cache Warmer for AssetConfigCache.
 */
class AssetConfigCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var AssetConfigCache
     */
    private $cache;

    public function __construct(AssetConfigCache $cache)
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
