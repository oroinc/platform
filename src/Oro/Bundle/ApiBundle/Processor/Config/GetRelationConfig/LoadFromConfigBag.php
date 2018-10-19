<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\RelationConfigMerger;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\LoadFromConfigBag as BaseLoadFromConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Loads configuration from "Resources/config/oro/api.yml".
 */
class LoadFromConfigBag extends BaseLoadFromConfigBag
{
    /** @var ConfigBagRegistry */
    private $configBagRegistry;

    /**
     * @param ConfigExtensionRegistry $configExtensionRegistry
     * @param ConfigLoaderFactory     $configLoaderFactory
     * @param ConfigBagRegistry       $configBagRegistry
     * @param RelationConfigMerger    $relationConfigMerger
     */
    public function __construct(
        ConfigExtensionRegistry $configExtensionRegistry,
        ConfigLoaderFactory $configLoaderFactory,
        ConfigBagRegistry $configBagRegistry,
        RelationConfigMerger $relationConfigMerger
    ) {
        parent::__construct($configExtensionRegistry, $configLoaderFactory, $relationConfigMerger);
        $this->configBagRegistry = $configBagRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfig($entityClass, $version, RequestType $requestType)
    {
        return $this->configBagRegistry->getConfigBag($requestType)->getRelationConfig($entityClass, $version);
    }
}
