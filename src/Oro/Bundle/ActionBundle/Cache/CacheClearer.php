<?php

namespace Oro\Bundle\ActionBundle\Cache;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class CacheClearer implements CacheClearerInterface
{
    /**
     * @var array|ConfigurationProviderInterface[]
     */
    private $providers = [];

    /**
     * @param ConfigurationProviderInterface $provider
     */
    public function addProvider(ConfigurationProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        foreach ($this->providers as $provider) {
            $provider->clearCache();
        }
    }
}
