<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;

class TestConfigRegistry
{
    /** @var TestConfigBag */
    private $configBag;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var RelationConfigProvider */
    private $relationConfigProvider;

    /** @var MetadataProvider */
    private $metadataProvider;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    /** @var bool */
    private $isResourcesCacheAffected = false;

    /**
     * @param TestConfigBag          $configBag
     * @param ConfigProvider         $configProvider
     * @param RelationConfigProvider $relationConfigProvider
     * @param MetadataProvider       $metadataProvider
     * @param ResourcesProvider      $resourcesProvider
     */
    public function __construct(
        TestConfigBag $configBag,
        ConfigProvider $configProvider,
        RelationConfigProvider $relationConfigProvider,
        MetadataProvider $metadataProvider,
        ResourcesProvider $resourcesProvider
    ) {
        $this->configBag = $configBag;
        $this->configProvider = $configProvider;
        $this->relationConfigProvider = $relationConfigProvider;
        $this->metadataProvider = $metadataProvider;
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * @param string $entityClass
     * @param array  $config
     * @param bool   $affectResourcesCache
     */
    public function appendEntityConfig($entityClass, array $config, $affectResourcesCache)
    {
        $this->configBag->appendEntityConfig($entityClass, $config);
        if ($affectResourcesCache) {
            $this->isResourcesCacheAffected = true;
        }
        $this->clearCaches();
    }

    public function restoreConfigs()
    {
        if ($this->configBag->restoreConfigs()) {
            $this->clearCaches();
        }
        $this->isResourcesCacheAffected = false;
    }

    private function clearCaches()
    {
        $this->configProvider->clearCache();
        $this->relationConfigProvider->clearCache();
        $this->metadataProvider->clearCache();
        if ($this->isResourcesCacheAffected) {
            $this->resourcesProvider->clearCache();
        }
    }
}
