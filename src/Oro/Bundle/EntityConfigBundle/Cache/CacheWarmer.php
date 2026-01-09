<?php

namespace Oro\Bundle\EntityConfigBundle\Cache;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up the entity configuration cache.
 */
class CacheWarmer implements CacheWarmerInterface
{
    /** @var ConfigCacheWarmer */
    private $configCacheWarmer;

    public function __construct(ConfigCacheWarmer $configCacheWarmer)
    {
        $this->configCacheWarmer = $configCacheWarmer;
    }

    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        $this->configCacheWarmer->warmUpCache();
        return [];
    }

    /**
     * {inheritdoc}
     */
    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }
}
