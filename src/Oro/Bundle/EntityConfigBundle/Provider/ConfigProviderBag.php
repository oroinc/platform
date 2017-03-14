<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Component\DependencyInjection\ServiceLink;

class ConfigProviderBag
{
    /** @var array [scope => provider service id, ...] */
    private $scopes;

    /** @var ServiceLink */
    private $configManagerLink;

    /** @var PropertyConfigBag */
    private $configBag;

    /** @var ConfigProvider[] [scope => ConfigProvider, ...] */
    private $providers = [];

    /** @var bool */
    private $allProvidersInitialized;

    /**
     * @param string[]          $scopes
     * @param ServiceLink       $configManagerLink
     * @param PropertyConfigBag $configBag
     */
    public function __construct(array $scopes, ServiceLink $configManagerLink, PropertyConfigBag $configBag)
    {
        $this->scopes = $scopes;
        $this->configManagerLink = $configManagerLink;
        $this->configBag = $configBag;
    }

    /**
     * Gets the configuration provider for the given scope.
     *
     * @param string $scope
     *
     * @return ConfigProvider|null
     */
    public function getProvider($scope)
    {
        if (isset($this->providers[$scope])) {
            return $this->providers[$scope];
        }

        $provider = null;
        if (!in_array($scope, $this->scopes, true)) {
            return null;
        }

        $provider = new ConfigProvider($this->configManagerLink->getService(), $scope, $this->configBag);
        $this->providers[$scope] = $provider;

        return $provider;
    }

    /**
     * Gets all configuration providers.
     *
     * @return ConfigProvider[] [scope => ConfigProvider, ...]
     */
    public function getProviders()
    {
        // ensure that all providers are loaded
        if (!$this->allProvidersInitialized) {
            foreach ($this->scopes as $scope) {
                $this->getProvider($scope);
            }
            $this->allProvidersInitialized = true;
        }

        return $this->providers;
    }
}
