<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\RelationDefinitionConfiguration;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\LoadFromConfigBag as BaseLoadFromConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;

/**
 * Loads configuration from "Resources/config/oro/api.yml".
 */
class LoadFromConfigBag extends BaseLoadFromConfigBag
{
    /** @var ConfigBag */
    protected $configBag;

    /** @var NodeInterface */
    private $configurationTree;

    /**
     * @param ConfigExtensionRegistry          $configExtensionRegistry
     * @param ConfigLoaderFactory              $configLoaderFactory
     * @param EntityHierarchyProviderInterface $entityHierarchyProvider
     * @param ConfigBag                        $configBag
     */
    public function __construct(
        ConfigExtensionRegistry $configExtensionRegistry,
        ConfigLoaderFactory $configLoaderFactory,
        EntityHierarchyProviderInterface $entityHierarchyProvider,
        ConfigBag $configBag
    ) {
        parent::__construct($configExtensionRegistry, $configLoaderFactory, $entityHierarchyProvider);
        $this->configBag = $configBag;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfig($entityClass, $version)
    {
        return $this->configBag->getRelationConfig($entityClass, $version);
    }

    /**
     * {@inheritdoc}
     */
    protected function mergeConfigs(array $config, array $parentConfig)
    {
        $processor = new Processor();

        return $processor->process($this->getConfigurationTree(), [$parentConfig, $config]);
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
        list(
            $extraSections,
            $configureCallbacks,
            $preProcessCallbacks,
            $postProcessCallbacks
            ) = $this->configExtensionRegistry->getConfigurationSettings();

        $configTreeBuilder = new TreeBuilder();
        $configuration     = new EntityConfiguration(
            ApiConfiguration::RELATIONS_SECTION,
            new RelationDefinitionConfiguration(),
            $extraSections,
            $this->configExtensionRegistry->getMaxNestingLevel()
        );
        $configuration->configure(
            $configTreeBuilder->root('related_entity')->children(),
            $configureCallbacks,
            $preProcessCallbacks,
            $postProcessCallbacks
        );

        return $configTreeBuilder->buildTree();
    }
}
