<?php

namespace Oro\Component\Config\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up a cache.
 *
 * Example of usage in DIC configuration file:
 * <code>
 * services:
 *     acme.my_configuration.warmer:
 *         class: Oro\Component\Config\Cache\ConfigCacheWarmer
 *         public: false
 *         arguments:
 *             - '@acme.my_configuration.provider'
 *         tags:
 *             - { name: kernel.cache_warmer }
 * </code>
 */
class ConfigCacheWarmer implements CacheWarmerInterface
{
    /** @var WarmableConfigCacheInterface */
    private $configCache;

    /** @var bool */
    private $optional;

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
