<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * A cache for entity configs
 */
class ConfigCache
{
    const FLAG_KEY = 0;
    const FIELDS_KEY = 1;
    const VALUES_KEY = 0;
    const FIELD_TYPE_KEY = 1;

    /** @var CacheProvider */
    protected $cache;

    /** @var CacheProvider */
    protected $modelCache;

    /** @var bool */
    protected $isDebug;

    /** @var array */
    private $localCache = [];

    /** @var array */
    private $localModelCache = [];

    /**
     * @param CacheProvider $cache
     * @param CacheProvider $modelCache
     * @param bool          $isDebug
     */
    public function __construct(CacheProvider $cache, CacheProvider $modelCache, $isDebug = false)
    {
        $this->cache      = $cache;
        $this->modelCache = $modelCache;
        $this->isDebug    = $isDebug;
    }

    /**
     * @param string $scope
     * @param string $className
     * @param bool   $localCacheOnly
     *
     * @return ConfigInterface|null
     */
    public function getEntityConfig($scope, $className, $localCacheOnly = false)
    {
        if (isset($this->localCache[$className])) {
            $cacheEntry = $this->localCache[$className];
        } else {
            $cacheEntry = !$localCacheOnly ? $this->fetchEntityConfig($className) : [];
        }

        return isset($cacheEntry[$scope])
            ? $cacheEntry[$scope]
            : null;
    }

    /**
     * @param string $scope
     * @param string $className
     * @param string $fieldName
     * @param bool   $localCacheOnly
     *
     * @return ConfigInterface|null
     */
    public function getFieldConfig($scope, $className, $fieldName, $localCacheOnly = false)
    {
        $cacheKey = $className . '.' . $fieldName;

        if (isset($this->localCache[$cacheKey])) {
            $cacheEntry = $this->localCache[$cacheKey];
        } else {
            $cacheEntry = !$localCacheOnly ? $this->fetchFieldConfig($cacheKey, $className, $fieldName) : [];
        }

        return isset($cacheEntry[$scope])
            ? $cacheEntry[$scope]
            : null;
    }

    /**
     * @param ConfigInterface $config
     * @param bool            $localCacheOnly
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function saveConfig(ConfigInterface $config, $localCacheOnly = false)
    {
        $configId = $config->getId();
        if ($this->isDebug && $configId instanceof FieldConfigId && null === $configId->getFieldType()) {
            // undefined field type can cause unpredictable logical bugs
            throw new \InvalidArgumentException(
                sprintf(
                    'A field config "%s::%s" with undefined field type cannot be cached.'
                    . ' It seems that there is some critical bug in entity config core functionality.'
                    . ' Please contact ORO team if you see this error.',
                    $configId->getClassName(),
                    $configId->getFieldName()
                )
            );
        }

        $className = $configId->getClassName();
        if ($configId instanceof FieldConfigId) {
            $fieldName  = $configId->getFieldName();
            $cacheKey   = $className . '.' . $fieldName;
            $cacheEntry = isset($this->localCache[$cacheKey])
                ? $this->localCache[$cacheKey]
                : $this->fetchFieldConfig($cacheKey, $className, $fieldName);
        } else {
            $cacheKey   = $className;
            $cacheEntry = isset($this->localCache[$cacheKey])
                ? $this->localCache[$cacheKey]
                : $this->fetchEntityConfig($className);
        }

        $cacheEntry[$configId->getScope()] = $config;

        $this->localCache[$cacheKey] = $cacheEntry;

        return $localCacheOnly
            ? true
            : $this->pushConfig($cacheKey, $cacheEntry);
    }

    /**
     * @param string $className
     * @param bool   $localCacheOnly
     *
     * @return bool
     */
    public function deleteEntityConfig($className, $localCacheOnly = false)
    {
        unset($this->localCache[$className]);

        return $localCacheOnly
            ? true
            : $this->cache->delete($className);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param bool   $localCacheOnly
     *
     * @return bool
     */
    public function deleteFieldConfig($className, $fieldName, $localCacheOnly = false)
    {
        $cacheKey = $className . '.' . $fieldName;

        unset($this->localCache[$cacheKey]);

        return $localCacheOnly
            ? true
            : $this->cache->delete($cacheKey);
    }

    /**
     * Deletes cache entries for all configs.
     *
     * @return bool TRUE if the cache entries were successfully deleted; otherwise, FALSE.
     */
    public function deleteAllConfigs()
    {
        $this->localCache = [];

        return $this->cache->deleteAll();
    }

    /**
     * Flushes cache entries for all configs.
     *
     * @return bool TRUE if the cache entries were successfully flushed; otherwise, FALSE.
     */
    public function flushAllConfigs()
    {
        $this->localCache = [];

        return $this->cache->flushAll();
    }

    /**
     * Gets a flag indicates whether an entity or entity field is configurable or not.
     *
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return bool|null true if an entity or entity field is configurable;
     *                   false if not;
     *                   null if unknown (it means that "is configurable" flag was not set yet)
     */
    public function getConfigurable($className, $fieldName = null)
    {
        $cacheEntry = isset($this->localModelCache[$className])
            ? $this->localModelCache[$className]
            : $this->fetchConfigurable($className);

        if ($fieldName) {
            if (isset($cacheEntry[self::FIELDS_KEY][$fieldName])) {
                return $cacheEntry[self::FIELDS_KEY][$fieldName];
            }
        } elseif (isset($cacheEntry[self::FLAG_KEY])) {
            return $cacheEntry[self::FLAG_KEY];
        }

        return null;
    }

    /**
     * Sets a flag indicates whether an entity or entity field is configurable or not.
     *
     * @param bool        $flag TRUE if an entity or entity field is configurable; otherwise, FALSE
     * @param string      $className
     * @param string|null $fieldName
     * @param bool        $localCacheOnly
     *
     * @return boolean TRUE if the entry was successfully stored in the cache; otherwise, FALSE.
     */
    public function saveConfigurable($flag, $className, $fieldName = null, $localCacheOnly = false)
    {
        $cacheEntry = isset($this->localModelCache[$className])
            ? $this->localModelCache[$className]
            : $this->fetchConfigurable($className);

        if ($fieldName) {
            $cacheEntry[self::FIELDS_KEY][$fieldName] = $flag;
        } else {
            $cacheEntry[self::FLAG_KEY] = $flag;
        }
        $this->localModelCache[$className] = $cacheEntry;

        return $localCacheOnly
            ? true
            : $this->modelCache->save($className, $cacheEntry);
    }

    /**
     * Deletes cached "configurable" flags for all configs.
     *
     * @return bool TRUE if the cache entries were successfully deleted; otherwise, FALSE.
     */
    public function deleteAllConfigurable()
    {
        $this->localModelCache = [];

        return $this->modelCache->deleteAll();
    }

    /**
     * Flushes cached "configurable" flags for all configs.
     *
     * @return bool TRUE if the cache entries were successfully flushed; otherwise, FALSE.
     */
    public function flushAllConfigurable()
    {
        $this->localModelCache = [];

        return $this->modelCache->flushAll();
    }

    /**
     * @param string $className
     *
     * @return array
     */
    protected function fetchEntityConfig($className)
    {
        /** @var array $cacheEntry */
        $cacheEntry = $this->cache->fetch($className);
        if (false === $cacheEntry) {
            $cacheEntry = [];
        }

        if (!empty($cacheEntry)) {
            $unpacked = [];
            foreach ($cacheEntry as $scope => $values) {
                $unpacked[$scope] = new Config(
                    new EntityConfigId($scope, $className),
                    $values
                );
            }
            $cacheEntry = $unpacked;
        }

        $this->localCache[$className] = $cacheEntry;

        return $cacheEntry;
    }

    /**
     * @param string $cacheKey
     * @param string $className
     * @param string $fieldName
     *
     * @return array
     */
    protected function fetchFieldConfig($cacheKey, $className, $fieldName)
    {
        /** @var array $cacheEntry */
        $cacheEntry = $this->cache->fetch($cacheKey);
        if (false === $cacheEntry) {
            $cacheEntry = [];
        }

        if (!empty($cacheEntry)) {
            $unpacked  = [];
            $fieldType = $cacheEntry[self::FIELD_TYPE_KEY];
            foreach ($cacheEntry[self::VALUES_KEY] as $scope => $values) {
                $unpacked[$scope] = new Config(
                    new FieldConfigId($scope, $className, $fieldName, $fieldType),
                    $values
                );
            }
            $cacheEntry = $unpacked;
        }

        $this->localCache[$cacheKey] = $cacheEntry;

        return $cacheEntry;
    }

    /**
     * @param string $cacheKey
     * @param array  $cacheEntry
     *
     * @return bool
     */
    protected function pushConfig($cacheKey, $cacheEntry)
    {
        $packed    = [];
        $fieldType = false;
        /** @var Config $config */
        foreach ($cacheEntry as $scope => $config) {
            if (false === $fieldType) {
                $configId  = $config->getId();
                $fieldType = $configId instanceof FieldConfigId
                    ? $configId->getFieldType()
                    : null;
            }
            $packed[$scope] = $config->all();
        }
        if ($fieldType) {
            $packed = [self::VALUES_KEY => $packed, self::FIELD_TYPE_KEY => $fieldType];
        }

        return $this->cache->save($cacheKey, $packed);
    }

    /**
     * @param string $className
     *
     * @return array
     */
    protected function fetchConfigurable($className)
    {
        $cacheEntry = $this->modelCache->fetch($className);
        if (false === $cacheEntry) {
            $cacheEntry = [];
        }

        $this->localModelCache[$className] = $cacheEntry;

        return $cacheEntry;
    }
}
