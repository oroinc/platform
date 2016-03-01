<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * A cache for entity configs
 * IMPORTANT: A performance of this class is very crucial. Double check a performance during a refactoring.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigCache
{
    const FLAG_KEY = 0;
    const FIELDS_KEY = 1;
    const VALUES_KEY = 0;
    const FIELD_TYPE_KEY = 1;

    const ENTITY_CLASSES_KEY = '_entities';
    const FIELD_NAMES_KEY = '_fields_';

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
     * @param bool $localCacheOnly
     *
     * @return array|null
     */
    public function getEntities($localCacheOnly = false)
    {
        return $this->getList(self::ENTITY_CLASSES_KEY, $localCacheOnly);
    }

    /**
     * @param string $className
     * @param bool   $localCacheOnly
     *
     * @return array|null
     */
    public function getFields($className, $localCacheOnly = false)
    {
        return $this->getList(self::FIELD_NAMES_KEY . $className, $localCacheOnly);
    }

    /**
     * @param array $entities
     * @param bool  $localCacheOnly
     *
     * @return bool
     */
    public function saveEntities(array $entities, $localCacheOnly = false)
    {
        return $this->saveList(self::ENTITY_CLASSES_KEY, $entities, $localCacheOnly);
    }

    /**
     * @param string $className
     * @param array  $fields
     * @param bool   $localCacheOnly
     *
     * @return bool
     */
    public function saveFields($className, array $fields, $localCacheOnly = false)
    {
        return $this->saveList(self::FIELD_NAMES_KEY . $className, $fields, $localCacheOnly);
    }

    /**
     * @param bool $localCacheOnly
     *
     * @return bool
     */
    public function deleteEntities($localCacheOnly = false)
    {
        return $this->deleteList(self::ENTITY_CLASSES_KEY, $localCacheOnly);
    }

    /**
     * @param string $className
     * @param bool   $localCacheOnly
     *
     * @return bool
     */
    public function deleteFields($className, $localCacheOnly = false)
    {
        return $this->deleteList(self::FIELD_NAMES_KEY . $className, $localCacheOnly);
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
            // get from a local cache
            $cacheEntry = $this->localCache[$className];
        } elseif (!$localCacheOnly) {
            $cacheEntry = $this->fetchEntityConfig($className);
            // put to a local cache
            $this->localCache[$className] = $cacheEntry;
        } else {
            // a config was not found
            return null;
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
            // get from a local cache
            $cacheEntry = $this->localCache[$cacheKey];
        } elseif (!$localCacheOnly) {
            $cacheEntry = $this->fetchFieldConfig($cacheKey, $className, $fieldName);
            // put to a local cache
            $this->localCache[$cacheKey] = $cacheEntry;
        } else {
            // a config was not found
            return null;
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
        $this->deleteEntities($localCacheOnly);
        $this->deleteFields($className, $localCacheOnly);

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
        $this->deleteFields($className, $localCacheOnly);

        $cacheKey = $className . '.' . $fieldName;

        unset($this->localCache[$cacheKey]);

        return $localCacheOnly
            ? true
            : $this->cache->delete($cacheKey);
    }

    /**
     * Saves all config attributes for the given entity or field
     *
     * @param array  $values    [{scope} => [{name} => {value}, ...], ...]
     * @param string $className The class name of an entity
     *
     * @return bool
     */
    public function saveEntityConfigValues(array $values, $className)
    {
        return $this->cache->save($className, $values);
    }

    /**
     * Saves all config attributes for the given entity or field
     *
     * @param array  $values    [{scope} => [{name} => {value}, ...], ...]
     * @param string $className The class name of an entity
     * @param string $fieldName The name of a field
     * @param string $fieldType The data type of a field
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function saveFieldConfigValues(array $values, $className, $fieldName, $fieldType)
    {
        if ($this->isDebug && !$fieldType) {
            // undefined field type can cause unpredictable logical bugs
            throw new \InvalidArgumentException(
                sprintf(
                    'A field config "%s::%s" with undefined field type cannot be cached.'
                    . ' It seems that there is some critical bug in entity config core functionality.'
                    . ' Please contact ORO team if you see this error.',
                    $className,
                    $fieldName
                )
            );
        }

        return $this->cache->save(
            $className . '.' . $fieldName,
            [self::VALUES_KEY => $values, self::FIELD_TYPE_KEY => $fieldType]
        );
    }

    /**
     * Deletes cache entries for all configs.
     *
     * @param bool $localCacheOnly
     *
     * @return bool TRUE if the cache entries were successfully deleted; otherwise, FALSE.
     */
    public function deleteAllConfigs($localCacheOnly = false)
    {
        $this->localCache = [];

        return $localCacheOnly
            ? true
            : $this->cache->deleteAll();
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
        /** @var array $cacheEntry */
        if (isset($this->localModelCache[$className])) {
            $cacheEntry = $this->localModelCache[$className];
        } else {
            $cacheEntry = $this->modelCache->fetch($className);
            if (false === $cacheEntry) {
                $cacheEntry = [];
            }
            // put to a local cache
            $this->localModelCache[$className] = $cacheEntry;
        }

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
        /** @var array $cacheEntry */
        if (isset($this->localModelCache[$className])) {
            $cacheEntry = $this->localModelCache[$className];
        } else {
            $cacheEntry = $this->modelCache->fetch($className);
            if (false === $cacheEntry) {
                $cacheEntry = [];
            }
        }

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
     * Sets flags for entity and all its fields indicates whether they are configurable or not.
     * Be careful using this method because it completely replaces existing flags
     *
     * @param string      $className
     * @param bool        $classFlag  TRUE if an entity is configurable; otherwise, FALSE
     * @param string|null $fieldFlags [field_name => flag, ...]
     *                                flag = TRUE if a field is configurable; otherwise, FALSE
     *
     * @return boolean TRUE if the entry was successfully stored in the cache; otherwise, FALSE.
     */
    public function saveConfigurableValues($className, $classFlag, $fieldFlags)
    {
        $cacheEntry = [
            self::FLAG_KEY   => $classFlag,
            self::FIELDS_KEY => $fieldFlags
        ];

        $this->localModelCache[$className] = $cacheEntry;

        return $this->modelCache->save($className, $cacheEntry);
    }

    /**
     * Deletes cached "configurable" flags for all configs.
     *
     * @param bool $localCacheOnly
     *
     * @return bool TRUE if the cache entries were successfully deleted; otherwise, FALSE.
     */
    public function deleteAllConfigurable($localCacheOnly = false)
    {
        $this->localModelCache = [];

        return $localCacheOnly
            ? true
            : $this->modelCache->deleteAll();
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
     * @param string $cacheKey
     * @param bool   $localCacheOnly
     *
     * @return array|null
     */
    protected function getList($cacheKey, $localCacheOnly)
    {
        if (isset($this->localCache[$cacheKey])) {
            $result = $this->localCache[$cacheKey];

            return $result === false
                ? null
                : $result;
        }

        if ($localCacheOnly) {
            return null;
        }

        $result = $this->cache->fetch($cacheKey);

        $this->localCache[$cacheKey] = $result;

        return $result === false
            ? null
            : $result;
    }

    /**
     * @param string $cacheKey
     * @param array  $items
     * @param bool   $localCacheOnly
     *
     * @return bool
     */
    protected function saveList($cacheKey, $items, $localCacheOnly)
    {
        $this->localCache[$cacheKey] = $items;

        return $localCacheOnly
            ? true
            : $this->cache->save($cacheKey, $items);
    }

    /**
     * @param string $cacheKey
     * @param bool   $localCacheOnly
     *
     * @return bool
     */
    protected function deleteList($cacheKey, $localCacheOnly)
    {
        unset($this->localCache[$cacheKey]);

        return $localCacheOnly
            ? true
            : $this->cache->delete($cacheKey);
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
        } elseif (!empty($cacheEntry)) {
            $unpacked = [];
            foreach ($cacheEntry as $scope => $values) {
                $unpacked[$scope] = new Config(
                    new EntityConfigId($scope, $className),
                    $values
                );
            }
            $cacheEntry = $unpacked;
        }

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
        } elseif (!empty($cacheEntry)) {
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
            $packed[$scope] = $config->getValues();
        }
        if ($fieldType) {
            $packed = [self::VALUES_KEY => $packed, self::FIELD_TYPE_KEY => $fieldType];
        }

        return $this->cache->save($cacheKey, $packed);
    }
}
