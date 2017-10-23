<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Provider\RelationConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;

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

    /** @var ResourcesCache */
    private $resourcesCache;

    /**
     * @param TestConfigBag          $configBag
     * @param ConfigProvider         $configProvider
     * @param RelationConfigProvider $relationConfigProvider
     * @param MetadataProvider       $metadataProvider
     * @param ResourcesCache         $resourcesCache
     */
    public function __construct(
        TestConfigBag $configBag,
        ConfigProvider $configProvider,
        RelationConfigProvider $relationConfigProvider,
        MetadataProvider $metadataProvider,
        ResourcesCache $resourcesCache
    ) {
        $this->configBag = $configBag;
        $this->configProvider = $configProvider;
        $this->relationConfigProvider = $relationConfigProvider;
        $this->metadataProvider = $metadataProvider;
        $this->resourcesCache = $resourcesCache;
    }

    /**
     * @param string $entityClass
     * @param array  $config
     */
    public function appendEntityConfig($entityClass, array $config)
    {
        $this->configBag->appendEntityConfig($entityClass, $config);
        $this->clearCaches();
    }

    public function restoreConfigs()
    {
        if ($this->configBag->restoreConfigs()) {
            $this->clearCaches();
        }
    }

    private function clearCaches()
    {
        $this->configProvider->clearCache();
        $this->relationConfigProvider->clearCache();
        $this->metadataProvider->clearCache();
        $this->resourcesCache->clear();
    }
}
