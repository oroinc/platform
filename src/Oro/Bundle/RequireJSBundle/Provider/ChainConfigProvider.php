<?php

namespace Oro\Bundle\RequireJSBundle\Provider;

class ChainConfigProvider
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
     * @param ConfigProviderInterface $configProvider
     *
     * @return ChainConfigProvider
     */
    public function addProvider(ConfigProviderInterface $configProvider)
    {
        $this->providers[] = $configProvider;

        return $this;
    }
}