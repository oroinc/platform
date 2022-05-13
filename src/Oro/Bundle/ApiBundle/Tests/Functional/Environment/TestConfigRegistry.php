<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;

class TestConfigRegistry
{
    private ConfigBagRegistry $configBagRegistry;
    private ConfigProvider $configProvider;
    private MetadataProvider $metadataProvider;
    private ResourcesProvider $resourcesProvider;
    private bool $isResourcesCacheAffected = false;

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

    public function appendEntityConfig(
        RequestType $requestType,
        string $entityClass,
        array $config,
        bool $affectResourcesCache
    ): void {
        $this->getConfigBag($requestType)->appendEntityConfig($entityClass, $config);
        if ($affectResourcesCache) {
            $this->isResourcesCacheAffected = true;
        }
        $this->clearCache($this->isResourcesCacheAffected);
    }

    public function restoreConfigs(RequestType $requestType): void
    {
        if ($this->getConfigBag($requestType)->restoreConfigs()) {
            $this->clearCache($this->isResourcesCacheAffected);
        }
        $this->isResourcesCacheAffected = false;
    }

    public function clearCache(bool $clearResourcesCache = false): void
    {
        $this->configProvider->reset();
        $this->metadataProvider->reset();
        if ($clearResourcesCache) {
            $this->resourcesProvider->clearCache();
        }
    }

    private function getConfigBag(RequestType $requestType): TestConfigBag
    {
        return $this->configBagRegistry->getConfigBag($requestType);
    }
}
