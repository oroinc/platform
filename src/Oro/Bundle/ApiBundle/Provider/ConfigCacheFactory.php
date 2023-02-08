<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\Config\Cache\ConfigCacheStateInterface;

/**
 * The factory to create an object is used to store API configuration cache.
 */
class ConfigCacheFactory
{
    private string $cacheDir;
    private bool $debug;
    private ConfigCacheWarmer $configCacheWarmer;
    /** @var ConfigCacheStateInterface[]|null */
    private ?array $dependencies = null;

    public function __construct(string $cacheDir, bool $debug)
    {
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    public function setConfigCacheWarmer(ConfigCacheWarmer $configCacheWarmer): void
    {
        $this->configCacheWarmer = $configCacheWarmer;
    }

    public function getCache(string $configKey): ConfigCacheFile
    {
        $cache = new ConfigCacheFile(
            sprintf('%s/%s.php', $this->cacheDir, $configKey),
            $this->debug,
            $configKey,
            $this->configCacheWarmer
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
     */
    public function addDependency(ConfigCacheStateInterface $configCache): void
    {
        $this->dependencies[] = $configCache;
    }
}
