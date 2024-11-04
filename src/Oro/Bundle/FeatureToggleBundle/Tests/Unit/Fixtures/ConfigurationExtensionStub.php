<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Fixtures;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\ProcessConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class ConfigurationExtensionStub implements ConfigurationExtensionInterface, ProcessConfigurationExtensionInterface
{
    #[\Override]
    public function extendConfigurationTree(NodeBuilder $node): void
    {
    }

    #[\Override]
    public function processConfiguration(array $configuration): array
    {
        return $configuration;
    }

    #[\Override]
    public function completeConfiguration(array $configuration): array
    {
        return $configuration;
    }

    #[\Override]
    public function clearConfigurationCache(): void
    {
    }
}
