<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

class ConfigurationManager
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
     * @param string $feature
     * @param string $node
     * @param null|mixed $default
     * @return mixed
     */
    public function get($feature, $node, $default = null)
    {
        $configuration = $this->configurationProvider->getFeaturesConfiguration();
        if (array_key_exists($feature, $configuration) && array_key_exists($node, $configuration[$feature])) {
            return $configuration[$feature][$node];
        }

        return $default;
    }

    /**
     * @param string $toggle
     * @return null|string
     */
    public function getFeatureByToggle($toggle)
    {
        $configuration = $this->configurationProvider->getFeaturesConfiguration();
        
        foreach ($configuration as $featureName => $featureConfig) {
            if (isset($featureConfig['toggle']) && $featureConfig['toggle'] == $toggle) {
                return $featureName;
            }
        }
        
        return null;
    }

    /**
     * @param string $resourceType
     * @param string $resource
     * @return array
     */
    public function getFeaturesByResource($resourceType, $resource)
    {
        $configuration = $this->configurationProvider->getResourcesConfiguration();
        if (array_key_exists($resourceType, $configuration)
            && array_key_exists($resource, $configuration[$resourceType])
        ) {
            return $configuration[$resourceType][$resource];
        }

        return [];
    }

    /**
     * @param string $resourceType
     *
     * @return array
     */
    public function getResourcesByType($resourceType)
    {
        $configuration = $this->configurationProvider->getResourcesConfiguration();

        return array_key_exists($resourceType, $configuration) ? $configuration[$resourceType] : [];
    }

    /**
     * @param string $feature
     * @return array
     */
    public function getFeatureDependencies($feature)
    {
        $configuration = $this->configurationProvider->getDependenciesConfiguration();
        if (array_key_exists($feature, $configuration)) {
            return $configuration[$feature];
        }

        return [];
    }

    /**
     * @param string $feature
     * @return array
     */
    public function getFeatureDependents($feature)
    {
        $configuration = $this->configurationProvider->getDependentsConfiguration();
        if (array_key_exists($feature, $configuration)) {
            return $configuration[$feature];
        }

        return [];
    }
}
