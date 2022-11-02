<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

/**
 * Provides helpful methods to get configuration that is loaded from "Resources/config/oro/features.yml" files.
 */
class ConfigurationManager
{
    private ConfigurationProvider $configurationProvider;

    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    public function get(string $feature, string $node, mixed $default = null): mixed
    {
        $config = $this->configurationProvider->getFeaturesConfiguration();

        return \array_key_exists($feature, $config) && \array_key_exists($node, $config[$feature])
            ? $config[$feature][$node]
            : $default;
    }

    public function getFeatureByToggle(string $toggle): ?string
    {
        $config = $this->configurationProvider->getTogglesConfiguration();

        return $config[$toggle] ?? null;
    }

    public function getFeaturesByResource(string $resourceType, string $resource): array
    {
        $config = $this->configurationProvider->getResourcesConfiguration();

        return $config[$resourceType][$resource] ?? [];
    }

    public function getResourcesByType(string $resourceType): array
    {
        $config = $this->configurationProvider->getResourcesConfiguration();

        return $config[$resourceType] ?? [];
    }

    public function getFeatureDependencies(string $feature): array
    {
        $config = $this->configurationProvider->getDependenciesConfiguration();

        return $config[$feature] ?? [];
    }

    public function getFeatureDependents(string $feature): array
    {
        $config = $this->configurationProvider->getDependentsConfiguration();

        return $config[$feature] ?? [];
    }
}
