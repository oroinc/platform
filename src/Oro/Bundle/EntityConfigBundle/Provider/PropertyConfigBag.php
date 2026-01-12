<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

/**
 * Manages scope-specific property configurations with lazy-loading and caching.
 *
 * This class acts as a container for property configuration data organized by scope, providing lazy-loaded
 * access to {@see PropertyConfigContainer} instances. It caches instantiated containers to avoid redundant object
 * creation and improves performance when accessing configuration for multiple scopes.
 */
class PropertyConfigBag
{
    /** @var array [scope => scope config, ...] */
    private $config;

    /** @var PropertyConfigContainer[] [scope => PropertyConfigContainer, ...] */
    private $configObjects = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Gets a configuration of the given scope.
     *
     * @param string $scope
     *
     * @return PropertyConfigContainer
     */
    public function getPropertyConfig($scope)
    {
        if (isset($this->configObjects[$scope])) {
            return $this->configObjects[$scope];
        }

        $propertyConfig = new PropertyConfigContainer(
            array_key_exists($scope, $this->config) ? $this->config[$scope] : []
        );
        $this->configObjects[$scope] = $propertyConfig;

        return $propertyConfig;
    }
}
