<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityDefinitionConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\TargetEntityDefinitionConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Provides functionality to merge two configurations loaded from
 * "entities" section of "Resources/config/oro/api.yml".
 */
class EntityConfigMerger
{
    /** @var ConfigExtensionRegistry */
    private $configExtensionRegistry;

    /** @var NodeInterface */
    private $configurationTree;

    /**
     * @param ConfigExtensionRegistry $configExtensionRegistry
     */
    public function __construct(ConfigExtensionRegistry $configExtensionRegistry)
    {
        $this->configExtensionRegistry = $configExtensionRegistry;
    }

    /**
     * Merges the given configs.
     *
     * @param array $config
     * @param array $parentConfig
     *
     * @return array
     */
    public function merge(array $config, array $parentConfig)
    {
        $processor = new Processor();

        return $processor->process(
            $this->getConfigurationTree(),
            [$parentConfig, $config]
        );
    }

    /**
     * @return string
     */
    protected function getConfigurationSectionName()
    {
        return ApiConfiguration::ENTITIES_SECTION;
    }

    /**
     * @return TargetEntityDefinitionConfiguration
     */
    protected function getConfigurationSection()
    {
        return new EntityDefinitionConfiguration();
    }

    /**
     * @return NodeInterface
     */
    private function getConfigurationTree()
    {
        if (null === $this->configurationTree) {
            $this->configurationTree = $this->createConfigurationTree();
        }

        return $this->configurationTree;
    }

    /**
     * @return NodeInterface
     */
    private function createConfigurationTree()
    {
        $configTreeBuilder = new TreeBuilder();
        $configuration = new EntityConfiguration(
            $this->getConfigurationSectionName(),
            $this->getConfigurationSection(),
            $this->configExtensionRegistry->getConfigurationSettings(),
            $this->configExtensionRegistry->getMaxNestingLevel()
        );
        $configuration->configure(
            $configTreeBuilder->root('root')->children()
        );

        return $configTreeBuilder->buildTree();
    }
}
