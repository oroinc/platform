<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\Config\Cache\ConfigCache as Cache;
use Oro\Component\Config\Cache\ConfigCacheStateInterface;
use Symfony\Component\Config\ConfigCacheInterface;

/**
 * The factory to create an object is used to store API configuration cache.
 */
class ConfigCacheFactory
{
    /** @var string */
    private $cacheDir;

    /** @var bool */
    private $debug;

    /** @var ConfigCacheStateInterface[]|null */
    private $dependencies;

    /**
     * @param string $cacheDir
     * @param bool   $debug
     */
    public function __construct(string $cacheDir, bool $debug)
    {
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    /**
     * @param string $configKey
     *
     * @return ConfigCacheInterface
     */
    public function getCache(string $configKey): ConfigCacheInterface
    {
        $cache = new Cache(
            sprintf('%s/%s.php', $this->cacheDir, $configKey),
            $this->debug
        );
        if ($this->dependencies) {
            foreach ($this->dependencies as $dependency) {
                $cache->addDependency($dependency);
            }
        }

        return $cache;
    }

    /**
     * Registers a cache the API configuration cache depends on.
     *
     * @param ConfigCacheStateInterface $configCache
     */
    public function addDependency(ConfigCacheStateInterface $configCache): void
    {
        $this->dependencies[] = $configCache;
    }
}
