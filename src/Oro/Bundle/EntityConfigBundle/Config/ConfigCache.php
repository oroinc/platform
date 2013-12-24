<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * Cache for ConfigInterface
 */
class ConfigCache
{
    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var CacheProvider
     */
    protected $modelCache;

    /**
     * @param CacheProvider $cache
     * @param CacheProvider $modelCache
     */
    public function __construct(CacheProvider $cache, CacheProvider $modelCache)
    {
        $this->cache      = $cache;
        $this->modelCache = $modelCache;
    }

    /**
     * @param ConfigIdInterface $configId
     * @return ConfigInterface
     */
    public function loadConfigFromCache(ConfigIdInterface $configId)
    {
        return $this->cache->fetch($this->buildConfigCacheKey($configId));
    }

    /**
     * @param ConfigInterface $config
     * @return bool
     */
    public function putConfigInCache(ConfigInterface $config)
    {
        return $this->cache->save($this->buildConfigCacheKey($config->getId()), $config);
    }

    /**
     * @param ConfigIdInterface $configId
     * @return bool
     */
    public function removeConfigFromCache(ConfigIdInterface $configId)
    {
        return $this->cache->delete($this->buildConfigCacheKey($configId));
    }

    /**
     * @return bool
     */
    public function removeAll()
    {
        return $this->cache->deleteAll();
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return bool|null
     */
    public function getConfigurable($className, $fieldName = null)
    {
        return $this->modelCache->fetch($this->buildModelCacheKey($className, $fieldName));
    }

    /**
     * @param        $value
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    public function setConfigurable($value, $className, $fieldName = null)
    {
        return $this->modelCache->save($this->buildModelCacheKey($className, $fieldName), $value);
    }

    /**
     * @return bool
     */
    public function removeAllConfigurable()
    {
        return $this->modelCache->deleteAll();
    }

    /**
     * Returns a string unique identifies each config model
     *
     * @param string      $className
     * @param string|null $fieldName
     * @return string
     */
    protected function buildModelCacheKey($className, $fieldName = null)
    {
        return $fieldName
            ? sprintf('%s_%s', $className, $fieldName)
            : $className;
    }

    /**
     * Returns a string unique identifies each config item
     *
     * @param ConfigIdInterface $configId
     * @return string
     */
    protected function buildConfigCacheKey(ConfigIdInterface $configId)
    {
        return $configId instanceof FieldConfigId
            ? sprintf('%s_%s_%s', $configId->getScope(), $configId->getClassName(), $configId->getFieldName())
            : sprintf('%s_%s', $configId->getScope(), $configId->getClassName());
    }
}
