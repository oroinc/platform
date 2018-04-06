<?php

namespace Oro\Bundle\DataGridBundle\Cache;

use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class DatagridConfigurationCacheWarmer implements CacheWarmerInterface
{
    /** @var ConfigurationProvider */
    protected $configurationProvider;

    /**
     * @param ConfigurationProvider $configurationProvider
     */
    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->configurationProvider->loadConfiguration();
    }
}
