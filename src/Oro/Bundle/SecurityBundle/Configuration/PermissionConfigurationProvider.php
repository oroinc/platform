<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Merger\ConfigurationMerger;

class PermissionConfigurationProvider
{
    const ROOT_NODE_NAME = 'oro_permissions';

    /** @var PermissionListConfiguration */
    protected $permissionConfiguration;

    /** @var array */
    protected $kernelBundles;

    /** @var string */
    protected $configPath = 'Resources/config/oro/permissions.yml';

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
        $permissions = $this->parseConfiguration($this->loadConfiguration());

        if ($acceptedPermissions !== null) {
            foreach ($permissions as $permissionName => $permissionConfiguration) {
                // skip not used permissions
                if (!in_array($permissionName, $acceptedPermissions, true)) {
                    unset($permissions[$permissionName]);
                }
            }
        }

        return $permissions;
    }

    /**
     * @return array
     */
    protected function loadConfiguration()
    {
        $configLoader = new CumulativeConfigLoader('oro_security', new YamlCumulativeFileLoader($this->configPath));

        $resources = $configLoader->load();
        $configs = [];

        foreach ($resources as $resource) {
            if (array_key_exists(self::ROOT_NODE_NAME, $resource->data)) {
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
}
