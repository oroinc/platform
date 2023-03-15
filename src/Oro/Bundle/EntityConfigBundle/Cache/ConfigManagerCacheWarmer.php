<?php

namespace Oro\Bundle\EntityConfigBundle\Cache;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Clears the ConfigManager caches if they were warmed up,
 * because they must be empty after warmup
 */
class ConfigManagerCacheWarmer implements CacheWarmerInterface
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function isOptional()
    {
        return false;
    }

    public function warmUp(string $cacheDir)
    {
        $this->configManager->clear();
    }
}
