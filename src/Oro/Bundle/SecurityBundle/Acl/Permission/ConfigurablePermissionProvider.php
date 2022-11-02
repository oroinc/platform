<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

/**
 * This class is responsible to get configurable permissions.
 */
class ConfigurablePermissionProvider
{
    public const DEFAULT_CONFIGURABLE_NAME = 'default';

    /** @var ConfigurablePermissionConfigurationProvider */
    private $configurationProvider;

    public function __construct(ConfigurablePermissionConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param string $name name of Configurable Permission
     * @return ConfigurablePermission
     */
    public function get($name)
    {
        $data = $this->configurationProvider->getConfiguration();
        if (!isset($data[$name]) || !is_array($data[$name])) {
            return new ConfigurablePermission($name);
        }

        $data = $data[$name];

        return new ConfigurablePermission(
            $name,
            $this->getValue($data, 'default', false),
            $this->getValue($data, 'entities', []),
            $this->getValue($data, 'capabilities', []),
            $this->getValue($data, 'workflows', [])
        );
    }

    /**
     * @param array $data
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    private function getValue(array $data, $name, $default = null)
    {
        return array_key_exists($name, $data) ? $data[$name] : $default;
    }
}
