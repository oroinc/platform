<?php

namespace Oro\Bundle\EntityConfigBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;

class CacheWarmer implements CacheWarmerInterface
{
    /** @var ConfigCacheWarmer */
    private $configCacheWarmer;

    /**
     * @param ConfigCacheWarmer $configCacheWarmer
     */
    public function __construct(ConfigCacheWarmer $configCacheWarmer)
    {
        $this->configCacheWarmer = $configCacheWarmer;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->configCacheWarmer->warmUpCache();
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
