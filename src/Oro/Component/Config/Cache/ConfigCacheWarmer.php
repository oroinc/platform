<?php

namespace Oro\Component\Config\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up a cache.
 */
class ConfigCacheWarmer implements CacheWarmerInterface
{
    /** @var WarmableConfigCacheInterface */
    private $configCache;

    /** @var bool */
    private $optional;

    /**
     * @param WarmableConfigCacheInterface $configCache
     * @param bool                         $optional
     */
    public function __construct(WarmableConfigCacheInterface $configCache, bool $optional = true)
    {
        $this->configCache = $configCache;
        $this->optional = $optional;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return $this->optional;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->configCache->warmUpCache();
    }
}
