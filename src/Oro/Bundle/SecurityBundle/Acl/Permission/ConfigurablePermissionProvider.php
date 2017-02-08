<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class ConfigurablePermissionProvider
{
    const CACHE_ID = 'configurable_permissions';

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

        return new ConfigurablePermission(
            $name,
            array_key_exists('default', $data[$name]) ? $data[$name]['default'] : false,
            array_key_exists('entities', $data[$name]) ? $data[$name]['entities'] : [],
            array_key_exists('capabilities', $data[$name]) ? $data[$name]['capabilities'] : [],
            array_key_exists('workflows', $data[$name]) ? $data[$name]['workflows'] : []
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
        if (!$this->cache->contains(self::CACHE_ID)) {
            $this->buildCache();
        }

        return $this->cache->fetch(self::CACHE_ID);
    }
}
