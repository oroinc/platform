<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

class ConfigurablePermissionConfigurationProvider extends AbstractPermissionsConfigurationProvider
{
    const CONFIG_PATH = 'Resources/config/oro/configurable_permissions.yml';

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->parseConfiguration($this->loadConfiguration());
    }

    /**
     * {@inheritdoc}
     */
    protected function getRootName()
    {
        return ConfigurablePermissionListConfiguration::ROOT_NODE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigPath()
    {
        return self::CONFIG_PATH;
    }
}
