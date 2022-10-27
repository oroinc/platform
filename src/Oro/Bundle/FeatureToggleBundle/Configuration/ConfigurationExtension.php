<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The main entry point for feature configuration extensions.
 */
class ConfigurationExtension
{
    /** @var iterable|ConfigurationExtensionInterface[] */
    private iterable $extensions;

    /**
     * @paran iterable|ConfigurationExtensionInterface[] $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    public function extendConfigurationTree(NodeBuilder $node): void
    {
        foreach ($this->extensions as $extension) {
            $extension->extendConfigurationTree($node);
        }
    }

    public function processConfiguration(array $configuration): array
    {
        foreach ($this->extensions as $extension) {
            if ($extension instanceof ProcessConfigurationExtensionInterface) {
                $configuration = $extension->processConfiguration($configuration);
            }
        }

        return $configuration;
    }

    public function completeConfiguration(array $configuration): array
    {
        foreach ($this->extensions as $extension) {
            if ($extension instanceof ProcessConfigurationExtensionInterface) {
                $configuration = $extension->completeConfiguration($configuration);
            }
        }

        return $configuration;
    }

    public function clearConfigurationCache(): void
    {
        foreach ($this->extensions as $extension) {
            if ($extension instanceof ProcessConfigurationExtensionInterface) {
                $extension->clearConfigurationCache();
            }
        }
    }
}
