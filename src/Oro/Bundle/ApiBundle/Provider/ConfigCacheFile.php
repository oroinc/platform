<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\Config\Cache\ConfigCache as BaseConfigCache;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;

/**
 * Represents a file on disk for API configuration cache.
 */
class ConfigCacheFile extends BaseConfigCache implements WarmableConfigCacheInterface
{
    private string $configKey;
    private ConfigCacheWarmer $configCacheWarmer;

    public function __construct(
        string $file,
        bool $debug,
        string $configKey,
        ConfigCacheWarmer $configCacheWarmer
    ) {
        parent::__construct($file, $debug);
        $this->configKey = $configKey;
        $this->configCacheWarmer = $configCacheWarmer;
    }

    /**
     * {@inheritDoc}
     */
    public function warmUpCache(): void
    {
        $this->ensureDependenciesWarmedUp();
        $this->configCacheWarmer->warmUp($this->configKey);
    }
}
