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
    const ENTITY_CLASSES_KEY = '_entities';
    const FIELD_NAMES_KEY    = '_fields_';

    /** @var CacheProvider */
    private $cache;

    /** @var CacheProvider */
    private $modelCache;

    /** @var array */
    private $entities = [];

    /** @var array */
    private $fields = [];

    /** @var array|null */
    private $listEntities;

    /** @var array */
    private $listFields = [];

    /** @var array */
    private $configurableEntities = [];

    /** @var array */
    private $configurableFields = [];

    /**
     * @param CacheProvider $cache
     * @param CacheProvider $modelCache
     */
    public function __construct(CacheProvider $cache, CacheProvider $modelCache)
    {
        $this->cache = $cache;
        $this->modelCache = $modelCache;
    }

    /**
     * @param bool $localCacheOnly
     *
     * @return array|null
     */
    public function getEntities($localCacheOnly = false)
    {
        if (null === $this->listEntities && !$localCacheOnly) {
            $this->listEntities = $this->cache->fetch(self::ENTITY_CLASSES_KEY);
        }
        if (false === $this->listEntities) {
            return null;
        }

        return $this->listEntities;
    }

    /**
     * @param string $className
     * @param bool   $localCacheOnly
     *
     * @return array|null
     */
    public function getFields($className, $localCacheOnly = false)
    {
        $result = null;
        if (array_key_exists($className, $this->listFields)) {
            $result = $this->listFields[$className];
        } elseif (!$localCacheOnly) {
            $result = $this->cache->fetch(self::FIELD_NAMES_KEY . $className);
            if (false === $result) {
                $result = null;
            }
            $this->listFields[$className] = $result;
        }

        return $result;
    }

    /**
     * @param array $entities
     * @param bool  $localCacheOnly
     *
     * @return bool
     */
    public function saveEntities(array $entities, $localCacheOnly = false)
    {
        $this->listEntities = $entities;

        if ($localCacheOnly) {
            return true;
        }

        return $this->cache->save(self::ENTITY_CLASSES_KEY, $entities);
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
        $this->listFields[$className] = $fields;

        if ($localCacheOnly) {
            return true;
        }

        return $this->cache->save(self::FIELD_NAMES_KEY . $className, $fields);
    }

    /**
     * @param bool $localCacheOnly
     *
     * @return bool
     */
    public function deleteEntities($localCacheOnly = false)
    {
        $this->listEntities = null;

        if ($localCacheOnly) {
            return true;
        }

        return $this->cache->delete(self::ENTITY_CLASSES_KEY);
    }

    /**
     * @param string $className
     * @param bool   $localCacheOnly
     *
     * @return bool
     */
    public function deleteFields($className, $localCacheOnly = false)
    {
        unset($this->listFields[$className]);

        if ($localCacheOnly) {
            return true;
        }

        return $this->cache->delete(self::FIELD_NAMES_KEY . $className);
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
        if (isset($this->entities[$className])) {
            // get from a local cache
            $entry = $this->entities[$className];
        } elseif (!$localCacheOnly) {
            $entry = $this->cache->fetch($className);
            if (false === $entry) {
                $entry = [];
            }
            // put to a local cache
            $this->entities[$className] = $entry;
        } else {
            // a config was not found
            return null;
        }

        if (!isset($entry[$scope])) {
            return null;
        }

        $config = $entry[$scope];
        if (!$config instanceof ConfigInterface) {
            $config = new Config(
                new EntityConfigId($scope, $className),
                $config
            );
            $this->entities[$className][$scope] = $config;
        }

        return $config;
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
        if (isset($this->fields[$cacheKey])) {
            // get from a local cache
            $entry = $this->fields[$cacheKey];
        } elseif (!$localCacheOnly) {
            $entry = $this->cache->fetch($cacheKey);
            if (false === $entry) {
                $entry = [];
            }
            // put to a local cache
            $this->fields[$cacheKey] = $entry;
        } else {
            // a config was not found
            return null;
        }

        if (!isset($entry[0][$scope])) {
            return null;
        }

        $config = $entry[0][$scope];
        if (!$config instanceof ConfigInterface) {
            $config = new Config(
                new FieldConfigId($scope, $className, $fieldName, $entry[1]),
                $config
            );
            $this->fields[$cacheKey][0][$scope] = $config;
        }

        return $config;
    }

    /**
     * @param ConfigInterface $config
     * @param bool            $localCacheOnly
     *
     * @return bool
     */
    public function saveConfig(ConfigInterface $config, $localCacheOnly = false)
    {
        if ($config->getId() instanceof FieldConfigId) {
            return $this->saveFieldConfig($config, $localCacheOnly);
        }

        return $this->saveEntityConfig($config, $localCacheOnly);
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

        unset($this->entities[$className]);

        if ($localCacheOnly) {
            return true;
        }

        return $this->cache->delete($className);
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

        unset($this->fields[$cacheKey]);

        if ($localCacheOnly) {
            return true;
        }

        return $this->cache->delete($cacheKey);
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
     */
    public function saveFieldConfigValues(array $values, $className, $fieldName, $fieldType)
    {
        return $this->cache->save(
            $className . '.' . $fieldName,
            [$values, $fieldType]
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
        $this->entities = [];
        $this->fields = [];
        $this->listEntities = null;
        $this->listFields = [];

        if ($localCacheOnly) {
            return true;
        }

        return $this->cache->deleteAll();
    }

    /**
     * Flushes cache entries for all configs.
     *
     * @return bool TRUE if the cache entries were successfully flushed; otherwise, FALSE.
     */
    public function flushAllConfigs()
    {
        $this->entities = [];
        $this->fields = [];
        $this->listEntities = null;
        $this->listFields = [];

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
        if (!$className) {
            return null;
        }

        $this->ensureModelCacheLoaded($className);

        if ($fieldName) {
            $fields = $this->configurableFields[$className];
            if (array_key_exists($fieldName, $fields)) {
                return $fields[$fieldName];
            }

            return null;
        }

        return $this->configurableEntities[$className];
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
        if (!$className) {
            return false;
        }

        $this->ensureModelCacheLoaded($className);

        if ($fieldName) {
            $this->configurableFields[$className][$fieldName] = $flag;
        } else {
            $this->configurableEntities[$className] = $flag;
        }

        if ($localCacheOnly) {
            return true;
        }

        return $this->saveModelCache(
            $className,
            $this->configurableEntities[$className],
            $this->configurableFields[$className]
        );
    }

    /**
     * Sets flags for entity and all its fields indicates whether they are configurable or not.
     * Be careful using this method because it completely replaces existing flags
     *
     * @param string $className
     * @param bool   $classFlag  TRUE if an entity is configurable; otherwise, FALSE
     * @param array  $fieldFlags [field_name => flag, ...]
     *                           flag = TRUE if a field is configurable; otherwise, FALSE
     *
     * @return boolean TRUE if the entry was successfully stored in the cache; otherwise, FALSE.
     */
    public function saveConfigurableValues($className, $classFlag, $fieldFlags)
    {
        if (!$className) {
            return false;
        }

        $this->configurableEntities[$className] = $classFlag;
        $this->configurableFields[$className] = $fieldFlags;

        return $this->saveModelCache($className, $classFlag, $fieldFlags);
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
        $this->configurableEntities = [];
        $this->configurableFields = [];

        if ($localCacheOnly) {
            return true;
        }

        return $this->modelCache->deleteAll();
    }

    /**
     * Flushes cached "configurable" flags for all configs.
     *
     * @return bool TRUE if the cache entries were successfully flushed; otherwise, FALSE.
     */
    public function flushAllConfigurable()
    {
        $this->configurableEntities = [];
        $this->configurableFields = [];

        return $this->modelCache->flushAll();
    }

    /**
     * @param ConfigInterface $config
     * @param bool            $localCacheOnly
     *
     * @return bool
     */
    private function saveEntityConfig(ConfigInterface $config, $localCacheOnly = false)
    {
        $configId = $config->getId();
        $className = $configId->getClassName();
        /** @var array $entry */
        if (isset($this->entities[$className])) {
            $entry = $this->entities[$className];
        } else {
            $entry = $this->cache->fetch($className);
            if (false === $entry) {
                $entry = [];
            }
        }
        $entry[$configId->getScope()] = $config;
        $this->entities[$className] = $entry;

        if ($localCacheOnly) {
            return true;
        }

        $packed = [];
        foreach ($entry as $scope => $value) {
            if ($value instanceof ConfigInterface) {
                $packed[$scope] = $value->getValues();
            } else {
                $packed[$scope] = $value;
            }
        }

        return $this->cache->save($className, $packed);
    }

    /**
     * @param ConfigInterface $config
     * @param bool            $localCacheOnly
     *
     * @return bool
     */
    private function saveFieldConfig(ConfigInterface $config, $localCacheOnly = false)
    {
        /** @var FieldConfigId $configId */
        $configId = $config->getId();
        $className = $configId->getClassName();
        $fieldName = $configId->getFieldName();
        $cacheKey = $className . '.' . $fieldName;
        /** @var array $entry */
        if (isset($this->fields[$cacheKey])) {
            $entry = $this->fields[$cacheKey];
        } else {
            $entry = $this->cache->fetch($cacheKey);
            if (false === $entry) {
                $entry = [[], $configId->getFieldType()];
            }
        }
        $entry[0][$configId->getScope()] = $config;
        $this->fields[$cacheKey] = $entry;

        if ($localCacheOnly) {
            return true;
        }

        $packed = [];
        foreach ($entry[0] as $scope => $value) {
            if ($value instanceof ConfigInterface) {
                $packed[$scope] = $value->getValues();
            } else {
                $packed[$scope] = $value;
            }
        }

        return $this->cache->save($cacheKey, [$packed, $configId->getFieldType()]);
    }

    /**
     * @param string $className
     */
    private function ensureModelCacheLoaded($className)
    {
        if (!array_key_exists($className, $this->configurableEntities)) {
            $entry = $this->modelCache->fetch($className);
            // put to a local cache
            if (empty($entry)) {
                $this->configurableEntities[$className] = null;
                $this->configurableFields[$className] = [];
            } else {
                $this->configurableEntities[$className] = $entry[0];
                if (isset($entry[1])) {
                    $this->configurableFields[$className] = $entry[1];
                } else {
                    $this->configurableFields[$className] = [];
                }
            }
        }
    }

    /**
     * @param string    $className
     * @param bool|null $classFlag
     * @param array     $fieldFlags
     *
     * @return bool
     */
    private function saveModelCache($className, $classFlag, $fieldFlags)
    {
        if (empty($fieldFlags)) {
            if (null === $classFlag) {
                $entry = [];
            } else {
                $entry = [$classFlag];
            }
        } else {
            $entry = [$classFlag, $fieldFlags];
        }

        return $this->modelCache->save($className, $entry);
    }
}
