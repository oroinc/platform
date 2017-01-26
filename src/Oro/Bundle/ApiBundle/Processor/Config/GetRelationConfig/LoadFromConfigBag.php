<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig\MergeConfig\MergeRelationConfigHelper;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\LoadFromConfigBag as BaseLoadFromConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ResourceHierarchyProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Loads configuration from "Resources/config/oro/api.yml".
 */
class LoadFromConfigBag extends BaseLoadFromConfigBag
{
    /** @var ConfigBag */
    private $configBag;

    /**
     * @param ConfigExtensionRegistry   $configExtensionRegistry
     * @param ConfigLoaderFactory       $configLoaderFactory
     * @param ResourceHierarchyProvider $resourceHierarchyProvider
     * @param ConfigBag                 $configBag
     * @param MergeRelationConfigHelper $mergeRelationConfigHelper
     */
    public function __construct(
        ConfigExtensionRegistry $configExtensionRegistry,
        ConfigLoaderFactory $configLoaderFactory,
        ResourceHierarchyProvider $resourceHierarchyProvider,
        ConfigBag $configBag,
        MergeRelationConfigHelper $mergeRelationConfigHelper
    ) {
        parent::__construct(
            $configExtensionRegistry,
            $configLoaderFactory,
            $resourceHierarchyProvider,
            $mergeRelationConfigHelper
        );
        $this->configBag = $configBag;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfig($entityClass, $version, RequestType $requestType)
    {
        return $this->configBag->getRelationConfig($entityClass, $version);
    }
}
