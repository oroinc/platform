<?php

namespace Oro\Bundle\RequireJSBundle\Manager;

use Oro\Bundle\RequireJSBundle\Provider\ConfigProviderInterface;

class ConfigProviderManager
{
    /**
     * @var ConfigProviderInterface[]
     */
    protected $providers = [];

    /**
     * @return ConfigProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * @param $alias
     *
     * @return ConfigProviderInterface|null
     */
    public function getProvider($alias)
    {
        if (array_key_exists($alias, $this->providers)) {
            return $this->providers[$alias];
        }

        return null;
    }

    /**
     * @param ConfigProviderInterface $configProvider
     * @param $alias
     *
     * @return ConfigProviderManager
     */
    public function addProvider(ConfigProviderInterface $configProvider, $alias)
    {
        $this->providers[$alias] = $configProvider;

        return $this;
    }
}
