<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

class PropertyConfigBag
{
    /** @var array [scope => scope config, ...] */
    private $config;

    /** @var PropertyConfigContainer[] [scope => PropertyConfigContainer, ...] */
    private $configObjects = [];

    /**
     * @param array $config
     */
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
