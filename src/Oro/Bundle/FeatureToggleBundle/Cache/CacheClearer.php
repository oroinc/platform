<?php

namespace Oro\Bundle\FeatureToggleBundle\Cache;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class CacheClearer implements CacheClearerInterface
{
    /**
     * @var ConfigurationProvider
     */
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
    public function clear($cacheDir)
    {
        $this->configurationProvider->clearCache();
    }
}
