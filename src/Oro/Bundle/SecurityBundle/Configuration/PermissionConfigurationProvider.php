<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\Finder;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Merger\ConfigurationMerger;

class PermissionConfigurationProvider
{
    /** @var PermissionListConfiguration */
    protected $permissionConfiguration;

    /** @var array */
    protected $kernelBundles;

    /** @var string */
    protected $configPath = 'Resources/config/permissions.yml';

    /**
     * @param PermissionListConfiguration $permissionConfiguration
     * @param array $kernelBundles
     */
    public function __construct(PermissionListConfiguration $permissionConfiguration, array $kernelBundles)
    {
        $this->permissionConfiguration = $permissionConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
    }

    /**
     * @param array $acceptedPermissions
     * @return array
     */
    public function getPermissionConfiguration(array $acceptedPermissions = null)
    {
        $configs = $this->loadConfiguration();
        $permissionsData = $this->parseConfiguration($configs);
        $permissions = [];
        foreach ($permissionsData as $permissionName => $permissionConfiguration) {
            // skip not used permissions
            if ($acceptedPermissions !== null && !in_array($permissionName, $acceptedPermissions, true)) {
                continue;
            }

            $permissions[$permissionName] = $permissionConfiguration;
        }

        return $permissions;
    }

    /**
     * @return array
     */
    protected function loadConfiguration()
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_security',
            new YamlCumulativeFileLoader($this->configPath)
        );

        $resources = $configLoader->load();
        $configs = [];

        foreach ($resources as $resource) {
            $configs[$resource->bundleClass] = $resource->data;
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
            $message = sprintf(
                'Can\'t parse permission configuration. %s',
                $exception->getMessage()
            );
            throw new InvalidConfigurationException($message);
        }

        return $permissionsData;
    }
}
