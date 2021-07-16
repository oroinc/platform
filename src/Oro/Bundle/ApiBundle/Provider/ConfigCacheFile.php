<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Component\Config\Cache\ConfigCache as BaseConfigCache;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;

/**
 * Represents a file on disk for API configuration cache.
 */
class ConfigCacheFile extends BaseConfigCache implements WarmableConfigCacheInterface
{
    /** @var string */
    private $configKey;

    /** @var ConfigCacheWarmer */
    private $configCacheWarmer;

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
     * {@inheritdoc}
     */
    public function warmUpCache(): void
    {
        $this->ensureDependenciesWarmedUp();
        $this->configCacheWarmer->warmUp($this->configKey);
    }
}
