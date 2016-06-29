<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

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
    protected function createConfigurationTree()
    {
        $configTreeBuilder = new TreeBuilder();
        $configuration     = new EntityConfiguration(
            ApiConfiguration::RELATIONS_SECTION,
            new RelationDefinitionConfiguration(),
            $this->configExtensionRegistry->getConfigurationSettings(),
            $this->configExtensionRegistry->getMaxNestingLevel()
        );
        $configuration->configure($configTreeBuilder->root('related_entity')->children());

        return $configTreeBuilder->buildTree();
    }
}
