<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ProcessConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class ConfigurationExtensionStub implements ConfigurationExtensionInterface, ProcessConfigurationExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function extendConfigurationTree(NodeBuilder $node): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function processConfiguration(array $configuration): array
    {
        return $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function completeConfiguration(array $configuration): array
    {
        return $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function clearConfigurationCache(): void
    {
    }
}
