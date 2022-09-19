<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for configurable security permissions configuration
 * that is loaded from "Resources/config/oro/configurable_permissions.yml" files.
 */
class ConfigurablePermissionConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/configurable_permissions.yml';

    /**
     * @param string   $cacheFile
     * @param bool     $debug
     */
    public function __construct(string $cacheFile, bool $debug)
    {
        parent::__construct($cacheFile, $debug);
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->doGetConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $configs = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_security_permissions', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (\array_key_exists(ConfigurablePermissionConfiguration::ROOT_NODE, $resource->data)) {
                $configs[$resource->bundleClass] = $resource->data;
            }
        }
        $merger = new ConfigurationMerger($this->getBundles());
        $mergedConfig = $merger->mergeConfiguration($configs);

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new ConfigurablePermissionConfiguration(),
            $mergedConfig
        );
    }

    protected function getBundles(): array
    {
        return CumulativeResourceManager::getInstance()->getBundles();
    }
}
