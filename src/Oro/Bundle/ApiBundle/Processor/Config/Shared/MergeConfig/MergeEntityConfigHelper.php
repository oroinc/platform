<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityDefinitionConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\TargetEntityDefinitionConfiguration;

class MergeEntityConfigHelper
{
    /** @var ConfigExtensionRegistry */
    protected $configExtensionRegistry;

    /**
     * @param ConfigExtensionRegistry $configExtensionRegistry
     */
    public function __construct(ConfigExtensionRegistry $configExtensionRegistry)
    {
        $this->configExtensionRegistry = $configExtensionRegistry;
    }

    /** @var NodeInterface */
    private $configurationTree;

    /**
     * Merges the given configs.
     *
     * @param array $config
     * @param array $parentConfig
     *
     * @return array
     */
    public function mergeConfigs(array $config, array $parentConfig)
    {
        $processor = new Processor();

        return $processor->process(
            $this->getConfigurationTree(),
            [$parentConfig, $config]
        );
    }

    /**
     * @return NodeInterface
     */
    protected function getConfigurationTree()
    {
        if (null === $this->configurationTree) {
            $this->configurationTree = $this->createConfigurationTree();
        }

        return $this->configurationTree;
    }

    /**
     * @return NodeInterface
     */
    protected function createConfigurationTree()
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
}
