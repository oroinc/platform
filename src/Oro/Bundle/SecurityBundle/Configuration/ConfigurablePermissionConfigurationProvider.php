<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for configurable security permissions configuration
 * that is loaded from "Resources/config/oro/configurable_permissions.yml" files.
 */
class ConfigurablePermissionConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/configurable_permissions.yml';

    /** @var string[] */
    private $bundles;

    /**
     * @param string   $cacheFile
     * @param bool     $debug
     * @param string[] $bundles
     */
    public function __construct(string $cacheFile, bool $debug, array $bundles)
    {
        parent::__construct($cacheFile, $debug);
        $this->bundles = $bundles;
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
        $configLoader = new CumulativeConfigLoader(
            'oro_security_permissions',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (\array_key_exists(ConfigurablePermissionConfiguration::ROOT_NODE, $resource->data)) {
                $configs[$resource->bundleClass] = $resource->data;
            }
        }

        $merger = new ConfigurationMerger($this->bundles);
        $mergedConfig = $merger->mergeConfiguration($configs);

        return CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new ConfigurablePermissionConfiguration(),
            $mergedConfig
        );
    }
}
