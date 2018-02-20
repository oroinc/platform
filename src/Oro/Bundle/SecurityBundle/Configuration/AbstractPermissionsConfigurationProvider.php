<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Merger\ConfigurationMerger;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

abstract class AbstractPermissionsConfigurationProvider
{
    /** @var PermissionConfigurationInterface */
    protected $permissionConfiguration;

    /** @var array */
    protected $kernelBundles;

    /**
     * @param PermissionConfigurationInterface $permissionConfiguration
     * @param array $kernelBundles
     */
    public function __construct(PermissionConfigurationInterface $permissionConfiguration, array $kernelBundles)
    {
        $this->permissionConfiguration = $permissionConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
    }

    /**
     * @return array
     */
    protected function loadConfiguration()
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_security',
            new YamlCumulativeFileLoader($this->getConfigPath())
        );

        $resources = $configLoader->load();
        $configs = [];

        foreach ($resources as $resource) {
            if (array_key_exists($this->getRootName(), $resource->data)) {
                $configs[$resource->bundleClass] = $resource->data;
            }
        }

        $merger = new ConfigurationMerger($this->kernelBundles);

        return $merger->mergeConfiguration($configs);
    }

    /**
     * @param array $configuration
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function parseConfiguration(array $configuration)
    {
        try {
            $permissionsData = $this->permissionConfiguration->processConfiguration($configuration);
        } catch (InvalidConfigurationException $exception) {
            throw new InvalidConfigurationException(
                sprintf('Can\'t parse permission configuration. %s', $exception->getMessage())
            );
        }

        return $permissionsData;
    }

    /**
     * @return string
     */
    abstract protected function getRootName();

    /**
     * @return string
     */
    abstract protected function getConfigPath();
}
