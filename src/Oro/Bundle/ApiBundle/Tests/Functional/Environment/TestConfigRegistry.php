<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;

class TestConfigRegistry
{
    /** @var ConfigBagRegistry */
    private $configBagRegistry;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var MetadataProvider */
    private $metadataProvider;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /** @var bool */
    private $isResourcesCacheAffected = false;

    public function __construct(
        ConfigBagRegistry $configBagRegistry,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider,
        ResourcesProvider $resourcesProvider
    ) {
        $this->configBagRegistry = $configBagRegistry;
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * @param RequestType $requestType
     * @param string      $entityClass
     * @param array       $config
     * @param bool        $affectResourcesCache
     */
    public function appendEntityConfig(RequestType $requestType, $entityClass, array $config, $affectResourcesCache)
    {
        $this->getConfigBag($requestType)->appendEntityConfig($entityClass, $config);
        if ($affectResourcesCache) {
            $this->isResourcesCacheAffected = true;
        }
        $this->clearCaches();
    }

    public function restoreConfigs(RequestType $requestType)
    {
        if ($this->getConfigBag($requestType)->restoreConfigs()) {
            $this->clearCaches();
        }
        $this->isResourcesCacheAffected = false;
    }

    /**
     * @param RequestType $requestType
     *
     * @return TestConfigBag
     */
    private function getConfigBag(RequestType $requestType)
    {
        return $this->configBagRegistry->getConfigBag($requestType);
    }

    private function clearCaches()
    {
        $this->configProvider->reset();
        $this->metadataProvider->reset();
        if ($this->isResourcesCacheAffected) {
            $this->resourcesProvider->clearCache();
        }
    }
}
