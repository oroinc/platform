<?php

namespace Oro\Bundle\ActionBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;

class CacheWarmer implements CacheWarmerInterface
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
    public function warmUp($cacheDir)
    {
        foreach ($this->providers as $provider) {
            $provider->warmUpCache();
        }
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }
}
