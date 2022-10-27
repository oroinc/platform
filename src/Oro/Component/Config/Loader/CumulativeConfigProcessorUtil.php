<?php

namespace Oro\Component\Config\Loader;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * Helper class to process configuration that is loaded from configuration files located in different bundles.
 */
class CumulativeConfigProcessorUtil
{
    /**
     * Parces, validates and merges an array of configurations.
     *
     * @param string                 $configFile       The configuration file
     * @param ConfigurationInterface $configDefinition The configuration class
     * @param array                  $configs          An array of configuration items to process
     *
     * @return array The processed configuration
     *
     * @throws InvalidConfigurationException When configuration has errors
     */
    public static function processConfiguration(
        string $configFile,
        ConfigurationInterface $configDefinition,
        array $configs
    ): array {
        $processor = new Processor();
        try {
            return $processor->processConfiguration($configDefinition, $configs);
        } catch (InvalidConfigurationException $e) {
            throw new InvalidConfigurationException(sprintf(
                'Cannot parse "%s" configuration. %s',
                $configFile,
                $e->getMessage()
            ));
        }
    }
}
