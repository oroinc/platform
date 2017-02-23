<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

class PermissionConfigurationProvider extends AbstractPermissionsConfigurationProvider
{
    const CONFIG_PATH = 'Resources/config/oro/permissions.yml';

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
     * {@inheritdoc}
     */
    protected function getRootName()
    {
        return PermissionListConfiguration::ROOT_NODE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigPath()
    {
        return self::CONFIG_PATH;
    }
}
