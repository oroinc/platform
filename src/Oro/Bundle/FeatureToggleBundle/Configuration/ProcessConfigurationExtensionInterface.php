<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

/**
 * The interface for extensions that need an additional processing of feature configuration.
 */
interface ProcessConfigurationExtensionInterface
{
    /**
     * Does an additional processing of feature configuration.
     *
     * @param array $configuration [feature => feature config, ...]
     *
     * @return array The updated configuration that is compatible
     *               with {@see \Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider}
     */
    public function processConfiguration(array $configuration): array;

    /**
     * Completes a configuration with options that are not stored
     * by {@see \Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider}.
     * This method is used by {@see \Oro\Bundle\FeatureToggleBundle\Command\ConfigDebugCommand}
     * to show full configuration of a feature.
     *
     * @param array $configuration [feature => feature config, ...]
     *
     * @return array The filled configuration
     */
    public function completeConfiguration(array $configuration): array;

    /**
     * Clears a cache that is used to store an additional configuration
     * that is not compatible with {@see \Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider}.
     */
    public function clearConfigurationCache(): void;
}
