<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityDefinitionConfiguration;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Provides functionality to merge two configurations loaded from
 * "entities" section of "Resources/config/oro/api.yml".
 */
class EntityConfigMerger
{
    private ConfigExtensionRegistry $configExtensionRegistry;
    private ?NodeInterface $configurationTree = null;

    public function __construct(ConfigExtensionRegistry $configExtensionRegistry)
    {
        $this->configExtensionRegistry = $configExtensionRegistry;
    }

    /**
     * Merges the given configs.
     */
    public function merge(array $config, array $parentConfig): array
    {
        return (new Processor())->process(
            $this->getConfigurationTree(),
            [$parentConfig, $config]
        );
    }

    private function getConfigurationTree(): NodeInterface
    {
        if (null === $this->configurationTree) {
            $this->configurationTree = $this->createConfigurationTree();
        }

        return $this->configurationTree;
    }

    private function createConfigurationTree(): NodeInterface
    {
        $configTreeBuilder = new TreeBuilder('root');
        $configuration = new EntityConfiguration(
            ApiConfiguration::ENTITIES_SECTION,
            new EntityDefinitionConfiguration(),
            $this->configExtensionRegistry->getConfigurationSettings(),
            $this->configExtensionRegistry->getMaxNestingLevel()
        );
        $configuration->configure(
            $configTreeBuilder->getRootNode()->children()
        );

        return $configTreeBuilder->buildTree();
    }
}
