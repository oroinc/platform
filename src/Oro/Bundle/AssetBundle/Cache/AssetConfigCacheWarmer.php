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

    #[\Override]
    public function isOptional(): bool
    {
        return false;
    }

    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->cache->warmUp($cacheDir);
        return [];
    }
}
