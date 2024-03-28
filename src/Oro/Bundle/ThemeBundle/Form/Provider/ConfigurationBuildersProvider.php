<?php

namespace Oro\Bundle\ThemeBundle\Form\Provider;

use Oro\Bundle\ThemeBundle\Exception\ConfigurationBuilderNotFoundException;
use Oro\Bundle\ThemeBundle\Form\Configuration\ConfigurationChildBuilderInterface;

/**
 * Provides configuration child builders
 */
class ConfigurationBuildersProvider
{
    /**
     * @param iterable<ConfigurationChildBuilderInterface> $configurationBuilders
     */
    public function __construct(
        private iterable $configurationBuilders
    ) {
    }

    public function getConfigurationTypes(): array
    {
        $result = [];

        foreach ($this->configurationBuilders as $configurationBuilder) {
            $result[] = $configurationBuilder::getType();
        }

        return $result;
    }

    public function getConfigurationBuilderByOption(array $option): ConfigurationChildBuilderInterface
    {
        foreach ($this->configurationBuilders as $configurationBuilder) {
            if ($configurationBuilder->supports($option)) {
                return $configurationBuilder;
            }
        }

        throw new ConfigurationBuilderNotFoundException(
            'There is no instance of ConfigurationChildBuilderInterface'
        );
    }
}
