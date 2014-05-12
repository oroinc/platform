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
     * Gets a flag indicates whether an entity or entity field is configurable or not.
     *
     * @param string      $className
     * @param string|null $fieldName
     * @return bool|null true if an entity or entity field is configurable;
     *                   false if not;
     *                   null if unknown (it means that "is configurable" flag was not set yet)
     */
    public function getConfigurable($className, $fieldName = null)
    {
        $flag = $this->modelCache->fetch(
            $this->buildModelCacheKey($className, $fieldName)
        );

        if ($flag === false) {
            // no cache entry exists
            $flag = null;
        } elseif ($flag === null) {
            // no cache entry exists and it say that an entity or entity field is not configurable
            $flag = false;
        }

        return $flag;
    }

    /**
     * Sets a flag indicates whether an entity or entity field is configurable or not.
     *
     * @param bool        $flag      true if an entity or entity field is configurable; otherwise, false
     * @param string      $className
     * @param string|null $fieldName
     * @return bool
     */
    public function setConfigurable($flag, $className, $fieldName = null)
    {
        return $this->modelCache->save(
            $this->buildModelCacheKey($className, $fieldName),
            $flag === false ? null : $flag
        );
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
