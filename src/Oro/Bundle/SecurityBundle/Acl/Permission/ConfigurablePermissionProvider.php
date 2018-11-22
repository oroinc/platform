<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

/**
 * This class is responsible to get configurable permissions.
 */
class ConfigurablePermissionProvider
{
    const CACHE_ID = 'configurable_permissions';
    const DEFAULT_CONFIGURABLE_NAME = 'default';

    /** @var ConfigurablePermissionConfigurationProvider */
    private $configurationProvider;

    /** @var CacheProvider */
    private $cache;

    /**
     * @param ConfigurablePermissionConfigurationProvider $configurationProvider
     * @param CacheProvider $cache
     */
    public function __construct(
        ConfigurablePermissionConfigurationProvider $configurationProvider,
        CacheProvider $cache
    ) {
        $this->configurationProvider = $configurationProvider;
        $this->cache = $cache;
    }

    /**
     * @param string $name name of Configurable Permission
     * @return ConfigurablePermission
     */
    public function get($name)
    {
        $data = $this->getConfigurablePermissionsData();
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

    public function buildCache()
    {
        $this->cache->save(self::CACHE_ID, $this->configurationProvider->getConfiguration());
    }

    /**
     * @return array
     */
    private function getConfigurablePermissionsData()
    {
        $configuration = $this->cache->fetch(self::CACHE_ID);
        if (false === $configuration) {
            $configuration = $this->configurationProvider->getConfiguration();
            $this->cache->save(self::CACHE_ID, $configuration);
        }

        return $configuration;
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
