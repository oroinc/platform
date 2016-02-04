<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\Finder;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Merger\ConfigurationMerger;

class PermissionConfigurationProvider
{
    const ROOT_NODE_NAME = 'permissions';

    /** @var PermissionListConfiguration */
    protected $permissionConfiguration;

    /** @var array */
    protected $kernelBundles;

    /**
     * @var string
     */
    protected $configPath = 'Resources/config/permissions.yml';

    /**
     * @param PermissionListConfiguration $permissionConfiguration
     */
    public function __construct(PermissionListConfiguration $permissionConfiguration, array $kernelBundles)
    {
        $this->permissionConfiguration = $permissionConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
    }

    /**
     * @param array $usedPermissions
     * @return array
     */
    public function getPermissionConfiguration(array $usedPermissions = null)
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
        $configs = $merger->mergeConfiguration($configs);

        $permissionsData = $this->parseConfiguration($configs);
        $permissions = [];
        foreach ($permissionsData as $permissionName => $permissionConfiguration) {
            // skip not used permissions
            if ($usedPermissions !== null && !in_array($permissionName, $usedPermissions, true)) {
                continue;
            }

            $permissions[$permissionName] = $permissionConfiguration;
        }

        return [self::ROOT_NODE_NAME => $permissions];
    }

    /**
     * @param array $configuration
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function parseConfiguration(array $configuration)
    {
        try {
            $permissionsData = [];
            if (!empty($configuration[self::ROOT_NODE_NAME])) {
                $permissionsData = $this->permissionConfiguration->processConfiguration(
                    $configuration[self::ROOT_NODE_NAME]
                );
            }
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
