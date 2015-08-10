<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

class ConfigProviderBag
{
    /** @var ConfigProvider[] */
    protected $providers = [];

    /**
     * @return ConfigProvider[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * @param ConfigProvider $provider
     *
     * @return $this
     */
    public function addProvider(ConfigProvider $provider)
    {
        $this->providers[$provider->getScope()] = $provider;

        return $this;
    }

    /**
     * @param $scope
     *
     * @return ConfigProvider
     */
    public function getProvider($scope)
    {
        return isset($this->providers[$scope]) ? $this->providers[$scope] : null;
    }

    /**
     * @param $scope
     *
     * @return bool
     */
    public function hasProvider($scope)
    {
        return isset($this->providers[$scope]);
    }
}
