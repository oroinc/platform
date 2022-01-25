<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\LogicException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The cache for entity configs.
 * IMPORTANT: A performance of this class is very crucial. Double check a performance during a refactoring.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ConfigCache
{
    private const ENTITY_CLASSES_KEY = '_entities';
    private const FIELD_NAMES_KEY    = '_fields_';

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var CacheItemPoolInterface */
    private $modelCache;

    /** @var string [scope => scope, ...] */
    private $scopes;

    /** @var array [scope => [class => config, ...], ...] */
    private $entities = [];

    /** @var array [scope => [class => [field name => [config, field type], ...], ...], ...] */
    private $fields = [];

    /** @var array|null [class => ['i' => entity ID, 'h' => "hidden" flag], ...] */
    private $listEntities;

    /** @var array [class => ['i' => field ID, 'h' => "hidden" flag, 't' => field type], ...] */
    private $listFields = [];

    /** @var array [class => "configurable" flag, ...] */
    private $configurableEntities = [];

    /** @var array [class => [field name => "configurable" flag], ...] */
    private $configurableFields = [];

    /** @var array|null [cache key => value to save, ...] */
    private $toSaveModel;

    /** @var array|null [cache key => value to save, ...] */
    private $toSave;

    /** @var array|null [cache key => TRUE, ...] */
    private $toDelete;

    /**
     * @param CacheItemPoolInterface $cache
     * @param CacheItemPoolInterface $modelCache
     * @param string[]      $scopes
     */
    public function __construct(CacheItemPoolInterface $cache, CacheItemPoolInterface $modelCache, array $scopes)
    {
        $this->cache = $cache;
        $this->modelCache = $modelCache;
        $this->scopes = $scopes;
    }

    /**
     * Starts a batch mode.
     * In this mode all changes are collected in a memory and they will be flushed to the by saveBatch() method.
     */
    public function beginBatch()
    {
        if (null !== $this->toSaveModel) {
            throw new LogicException('A batch already started. Nested batches are not supported.');
        }

        $this->toSaveModel = [];
        $this->toSave = [];
        $this->toDelete = [];
    }

    /**
     * Flushes all changes collected since beginBatch() method was called to the cache and stops a batch mode.
     */
    public function saveBatch()
    {
        if (null === $this->toSaveModel) {
            throw new LogicException('A batch is not started.');
        }

        try {
            if (!empty($this->toSaveModel)) {
                $this->saveMultiple($this->modelCache, $this->toSaveModel);
            }
            if (!empty($this->toSave)) {
                $this->saveMultiple($this->cache, $this->toSave);
            }
            if (!empty($this->toDelete)) {
                $keysToDelete = \array_keys($this->toDelete);
                \array_walk_recursive($keysToDelete, function (&$key) {
                    $key = $this->getCacheKey($key);
                });
                $this->cache->deleteItems($keysToDelete);
            }
        } finally {
            $this->toSaveModel = null;
            $this->toSave = null;
            $this->toDelete = null;
        }
    }

    /**
     * Stops a batch mode without flushing changes to the cache.
     */
    public function cancelBatch()
    {
        if (null === $this->toSaveModel) {
            return;
        }

        $this->toSaveModel = null;
        $this->toSave = null;
        $this->toDelete = null;
    }

    /**
     * @param bool $localCacheOnly Whether data should be retrieved only from memory cache
     *
     * @return array|null [class => ['i' => entity ID, 'h' => "hidden" flag], ...]
     *                    or NULL if there are no cached entities
     */
    public function getEntities($localCacheOnly = false)
    {
        if (null === $this->listEntities && !$localCacheOnly) {
            $this->listEntities = $this->cacheFetch(self::ENTITY_CLASSES_KEY);
        }
        if (false === $this->listEntities) {
            return null;
        }

        return $this->listEntities;
    }

    /**
     * @param string $className      FQCN of the entity
     * @param bool   $localCacheOnly Whether data should be retrieved only from memory cache
     *
     * @return array|null [field name => ['i' => field ID, 'h' => "hidden" flag, 't' => field type], ...]
     *                    or NULL if there are no cached fields
     */
    public function getFields($className, $localCacheOnly = false)
    {
        $result = null;
        if (\array_key_exists($className, $this->listFields)) {
            $result = $this->listFields[$className];
        } elseif (!$localCacheOnly) {
            $result = $this->cacheFetch(self::FIELD_NAMES_KEY . $className);
            if (false === $result) {
                $result = null;
            }
            $this->listFields[$className] = $result;
        }

        return $result;
    }

    /**
     * @param array $entities       [class => ['i' => entity ID, 'h' => "hidden" flag], ...]
     * @param bool  $localCacheOnly Whether data should be saved only to memory cache
     */
    public function saveEntities(array $entities, $localCacheOnly = false)
    {
        $this->listEntities = $entities;

        if (!$localCacheOnly) {
            $this->cacheSave(self::ENTITY_CLASSES_KEY, $entities);
        }
    }

    /**
     * @param string $className      FQCN of the entity
     * @param array  $fields         [field name => ['i' => field ID, 'h' => "hidden" flag, 't' => field type], ...]
     * @param bool   $localCacheOnly Whether data should be saved only to memory cache
     */
    public function saveFields($className, array $fields, $localCacheOnly = false)
    {
        $this->listFields[$className] = $fields;

        if (!$localCacheOnly) {
            $this->cacheSave(self::FIELD_NAMES_KEY . $className, $fields);
        }
    }

    /**
     * @param bool $localCacheOnly Whether data should be deleted only from memory cache
     */
    public function deleteEntities($localCacheOnly = false)
    {
        $this->listEntities = null;

        if (!$localCacheOnly) {
            $this->cacheDelete(self::ENTITY_CLASSES_KEY);
        }
    }

    /**
     * @param string $className      FQCN of the entity
     * @param bool   $localCacheOnly Whether data should be deleted only from memory cache
     */
    public function deleteFields($className, $localCacheOnly = false)
    {
        unset($this->listFields[$className]);

        if (!$localCacheOnly) {
            $this->cacheDelete(self::FIELD_NAMES_KEY . $className);
        }
    }

    /**
     * @param string $scope          The name of the configuration scope
     * @param string $className      FQCN of the entity
     * @param bool   $localCacheOnly Whether data should be retrieved only from memory cache
     *
     * @return ConfigInterface|null
     */
    public function getEntityConfig($scope, $className, $localCacheOnly = false)
    {
        /** @var array $entry [class => config, ...] */
        if (isset($this->entities[$scope])) {
            // get from a local cache
            $entry = $this->entities[$scope];
        } elseif (!$localCacheOnly) {
            $entry = $this->cacheFetch($scope);
            if (false === $entry) {
                $entry = [];
            }
            // put to a local cache
            $this->entities[$scope] = $entry;
        } else {
            // a config was not found
            return null;
        }

        if (!isset($entry[$className])) {
            return null;
        }

        $config = $entry[$className];
        if (!$config instanceof ConfigInterface) {
            $config = new Config(
                new EntityConfigId($scope, $className),
                $config
            );
            $this->entities[$scope][$className] = $config;
        }

        return $config;
    }

    /**
     * @param string $scope          The name of the configuration scope
     * @param string $className      FQCN of the entity
     * @param string $fieldName      The name of the field
     * @param bool   $localCacheOnly Whether data should be retrieved only from memory cache
     *
     * @return ConfigInterface|null
     */
    public function getFieldConfig($scope, $className, $fieldName, $localCacheOnly = false)
    {
        /** @var array $entry [field name => [config, field type], ...] */
        if (isset($this->fields[$scope][$className])) {
            // get from a local cache
            $entry = $this->fields[$scope][$className];
        } elseif (!$localCacheOnly) {
            $entry = $this->cacheFetch($this->getFieldConfigsCacheKey($className, $scope));
            if (false === $entry) {
                $entry = [];
            }
            // put to a local cache
            $this->fields[$scope][$className] = $entry;
        } else {
            // a config was not found
            return null;
        }

        if (!isset($entry[$fieldName])) {
            return null;
        }

        $fieldEntry = $entry[$fieldName];
        $config = $fieldEntry[0];
        if (!$config instanceof ConfigInterface) {
            $config = new Config(
                new FieldConfigId($scope, $className, $fieldName, $fieldEntry[1]),
                $config
            );
            $this->fields[$scope][$className][$fieldName][0] = $config;
        }

        return $config;
    }

    /**
     * @param ConfigInterface $config
     * @param bool            $localCacheOnly Whether data should be saved only to memory cache
     */
    public function saveConfig(ConfigInterface $config, $localCacheOnly = false)
    {
        if ($config->getId() instanceof FieldConfigId) {
            $this->saveFieldConfig($config, $localCacheOnly);
        } else {
            $this->saveEntityConfig($config, $localCacheOnly);
        }
    }

    /**
     * @param string $className      FQCN of the entity
     * @param bool   $localCacheOnly Whether data should be deleted only from memory cache
     */
    public function deleteEntityConfig($className, $localCacheOnly = false)
    {
        $this->deleteEntities($localCacheOnly);
        $this->deleteFields($className, $localCacheOnly);

        foreach ($this->scopes as $scope) {
            unset($this->entities[$scope][$className]);
            if (!$localCacheOnly) {
                if (empty($this->entities[$scope])) {
                    $this->cacheDelete($scope);
                } else {
                    $this->cacheSave(
                        $scope,
                        $this->packEntityConfigs($this->entities[$scope])
                    );
                }
            }
        }
    }

    /**
     * @param string $className      FQCN of the entity
     * @param string $fieldName      The name of the field
     * @param bool   $localCacheOnly Whether data should be deleted only from memory cache
     */
    public function deleteFieldConfig($className, $fieldName, $localCacheOnly = false)
    {
        $this->deleteFields($className, $localCacheOnly);

        foreach ($this->scopes as $scope) {
            unset($this->fields[$scope][$className][$fieldName]);
            if (!$localCacheOnly) {
                if (empty($this->fields[$scope][$className])) {
                    $this->cacheDelete($this->getFieldConfigsCacheKey($className, $scope));
                } else {
                    $this->cacheSave(
                        $this->getFieldConfigsCacheKey($className, $scope),
                        $this->packFieldConfigs($this->fields[$scope][$className])
                    );
                }
            }
        }
    }

    /**
     * Saves all config attributes for all entities of the given scope.
     *
     * @param array  $values [class => [key => value, ...], ...]
     * @param string $scope  The name of the configuration scope
     */
    public function saveEntityConfigValues(array $values, $scope)
    {
        $this->entities[$scope] = $values;
        $this->cacheSave($scope, $values);
    }

    /**
     * Saves all config attributes for all fields of the given entity.
     *
     * @param array  $values    [field name => [[key => value, ...], field type], ...]
     * @param string $scope     The name of the configuration scope
     * @param string $className FQCN of the entity
     */
    public function saveFieldConfigValues(array $values, $scope, $className)
    {
        $this->fields[$scope][$className] = $values;
        $this->cacheSave($this->getFieldConfigsCacheKey($className, $scope), $values);
    }

    /**
     * Deletes cache entries for all configs.
     *
     * @param bool $localCacheOnly Whether data should be deleted only from memory cache
     */
    public function deleteAllConfigs($localCacheOnly = false)
    {
        if (null !== $this->toSaveModel) {
            throw new LogicException('deleteAllConfigs() is not allowed inside a batch.');
        }

        $this->entities = [];
        $this->fields = [];
        $this->listEntities = null;
        $this->listFields = [];

        if (!$localCacheOnly) {
            $this->cache->clear();
        }
    }

    /**
     * Gets a flag indicates whether an entity or entity field is configurable or not.
     *
     * @param string      $className FQCN of the entity
     * @param string|null $fieldName The name of the field
     *
     * @return bool|null TRUE if an entity or entity field is configurable;
     *                   FALSE if not;
     *                   NULL if unknown (it means that "is configurable" flag was not set yet)
     */
    public function getConfigurable($className, $fieldName = null)
    {
        $this->ensureModelCacheLoaded($className);

        if ($fieldName) {
            $fields = $this->configurableFields[$className];
            if (\array_key_exists($fieldName, $fields)) {
                return $fields[$fieldName];
            }

            return null;
        }

        return $this->configurableEntities[$className];
    }

    /**
     * Sets a flag indicates whether an entity or entity field is configurable or not.
     *
     * @param bool        $flag           TRUE if an entity or entity field is configurable; otherwise, FALSE
     * @param string      $className      FQCN of the entity
     * @param string|null $fieldName      The name of the field
     * @param bool        $localCacheOnly Whether data should be saved only to memory cache
     */
    public function saveConfigurable($flag, $className, $fieldName = null, $localCacheOnly = false)
    {
        $this->ensureModelCacheLoaded($className);

        if ($fieldName) {
            $this->configurableFields[$className][$fieldName] = $flag;
        } else {
            $this->configurableEntities[$className] = $flag;
        }

        if (!$localCacheOnly) {
            $this->saveModelCache(
                $className,
                $this->configurableEntities[$className],
                $this->configurableFields[$className]
            );
        }
    }

    /**
     * Sets flags for entity and all its fields indicates whether they are configurable or not.
     * Be careful using this method because it completely replaces existing flags.
     *
     * @param string $className  FQCN of the entity
     * @param bool   $classFlag  TRUE if an entity is configurable; otherwise, FALSE
     * @param array  $fieldFlags [field_name => flag, ...]
     *                           flag = TRUE if a field is configurable; otherwise, FALSE
     */
    public function saveConfigurableValues($className, $classFlag, $fieldFlags)
    {
        $this->configurableEntities[$className] = $classFlag;
        $this->configurableFields[$className] = $fieldFlags;

        $this->saveModelCache($className, $classFlag, $fieldFlags);
    }

    /**
     * Deletes cached "configurable" flags for all configs.
     *
     * @param bool $localCacheOnly Whether data should be deleted only from memory cache
     */
    public function deleteAllConfigurable($localCacheOnly = false)
    {
        if (null !== $this->toSaveModel) {
            throw new LogicException('deleteAllConfigurable() is not allowed inside a batch.');
        }

        $this->configurableEntities = [];
        $this->configurableFields = [];

        if (!$localCacheOnly) {
            $this->modelCache->clear();
        }
    }

    /**
     * Deletes all cached configs.
     *
     * @param bool $localCacheOnly Whether data should be deleted only from memory cache
     */
    public function deleteAll($localCacheOnly = false)
    {
        $this->deleteAllConfigurable($localCacheOnly);
        $this->deleteAllConfigs($localCacheOnly);
    }

    /**
     * @param ConfigInterface $config
     * @param bool            $localCacheOnly Whether data should be saved only to memory cache
     */
    private function saveEntityConfig(ConfigInterface $config, $localCacheOnly = false)
    {
        $configId = $config->getId();
        $scope = $configId->getScope();
        $className = $configId->getClassName();
        /** @var array $entry [class => config, ...] */
        if (isset($this->entities[$scope])) {
            $entry = $this->entities[$scope];
        } else {
            $entry = $this->cacheFetch($scope);
            if (false === $entry) {
                $entry = [];
            }
        }
        $entry[$className] = $config;
        $this->entities[$scope] = $entry;

        if (!$localCacheOnly) {
            $this->cacheSave($scope, $this->packEntityConfigs($entry));
        }
    }

    /**
     * @param ConfigInterface $config
     * @param bool            $localCacheOnly Whether data should be saved only to memory cache
     */
    private function saveFieldConfig(ConfigInterface $config, $localCacheOnly = false)
    {
        /** @var FieldConfigId $configId */
        $configId = $config->getId();
        $scope = $configId->getScope();
        $className = $configId->getClassName();
        $fieldName = $configId->getFieldName();
        $fieldType = $configId->getFieldType();
        if (!$fieldType) {
            // undefined field type can cause unpredictable logical bugs
            throw new \InvalidArgumentException(sprintf(
                'A field config "%s::%s" with undefined field type cannot be cached.'
                . ' It seems that there is some critical bug in entity config core functionality.'
                . ' Please contact ORO team if you see this error.',
                $className,
                $fieldName
            ));
        }

        /** @var array $entry [field name => [config, field type], ...] */
        if (isset($this->fields[$scope][$className])) {
            $entry = $this->fields[$scope][$className];
        } else {
            $entry = $this->cacheFetch($this->getFieldConfigsCacheKey($className, $scope));
            if (false === $entry) {
                $entry = [];
            }
        }
        $entry[$fieldName] = [$config, $fieldType];
        $this->fields[$scope][$className] = $entry;

        if (!$localCacheOnly) {
            $this->cacheSave(
                $this->getFieldConfigsCacheKey($className, $scope),
                $this->packFieldConfigs($entry)
            );
        }
    }

    /**
     * @param string $className
     */
    private function ensureModelCacheLoaded($className)
    {
        if (!\array_key_exists($className, $this->configurableEntities)) {
            if (null !== $this->toSaveModel && isset($this->toSaveModel[$className])) {
                $entry = $this->toSaveModel[$className];
            } else {
                $cacheItem = $this->modelCache->getItem($this->getCacheKey($className));
                $entry = $cacheItem->isHit() ? $cacheItem->get() : null;
            }
            // put to a local cache
            if (empty($entry)) {
                $this->configurableEntities[$className] = null;
                $this->configurableFields[$className] = [];
            } else {
                $this->configurableEntities[$className] = $entry[0];
                $this->configurableFields[$className] = $entry[1] ?? [];
            }
        }
    }

    /**
     * @param string    $className
     * @param bool|null $classFlag
     * @param array     $fieldFlags
     */
    private function saveModelCache($className, $classFlag, $fieldFlags)
    {
        if (!empty($fieldFlags)) {
            $entry = [$classFlag, $fieldFlags];
        } elseif (null !== $classFlag) {
            $entry = [$classFlag];
        } else {
            $entry = [];
        }

        if (null === $this->toSaveModel) {
            $cacheItem = $this->modelCache->getItem($this->getCacheKey($className));
            $cacheItem->set($entry);
            $this->modelCache->save($cacheItem);
        } else {
            $this->toSaveModel[$className] = $entry;
        }
    }

    /**
     * @param string $key
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given key
     */
    private function cacheFetch($key)
    {
        $cacheItem = $this->cache->getItem($this->getCacheKey($key));
        if (null === $this->toSave) {
            return $cacheItem->isHit() ? $cacheItem->get() : false;
        }

        if (isset($this->toSave[$key])) {
            return $this->toSave[$key];
        }

        if (isset($this->toDelete[$key])) {
            return false;
        }

        return $cacheItem->isHit() ? $cacheItem->get() : false;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    private function cacheSave($key, $value)
    {
        if (null === $this->toSave) {
            $cacheItem = $this->cache->getItem($this->getCacheKey($key));
            $cacheItem->set($value);
            $this->cache->save($cacheItem);
        } else {
            $this->toSave[$key] = $value;
            unset($this->toDelete[$key]);
        }
    }

    /**
     * @param string $key
     */
    private function cacheDelete($key)
    {
        if (null === $this->toDelete) {
            $this->cache->deleteItem($this->getCacheKey($key));
        } else {
            $this->toDelete[$key] = true;
            unset($this->toSave[$key]);
        }
    }

    private function saveMultiple(CacheItemPoolInterface $cacheItemPool, array $cacheItems): void
    {
        foreach ($cacheItems as $cacheKey => $cacheItem) {
            $poolItem = $cacheItemPool->getItem($this->getCacheKey($cacheKey));
            $poolItem->set($cacheItem);
            $cacheItemPool->saveDeferred($poolItem);
        }
        $cacheItemPool->commit();
    }

    /**
     * @param array $entry [class => config, ...]
     *
     * @return array [class => config, ...]
     */
    private function packEntityConfigs($entry)
    {
        $packed = [];
        foreach ($entry as $className => $config) {
            if ($config instanceof ConfigInterface) {
                $config = $config->getValues();
            }
            $packed[$className] = $config;
        }

        return $packed;
    }

    /**
     * @param array $entry [field name => [config, field type], ...]
     *
     * @return array [field name => [config, field type], ...]
     */
    private function packFieldConfigs($entry)
    {
        $packed = [];
        foreach ($entry as $fieldName => $fieldEntry) {
            $config = $fieldEntry[0];
            if ($config instanceof ConfigInterface) {
                $config = $config->getValues();
            }
            $packed[$fieldName] = [$config, $fieldEntry[1]];
        }

        return $packed;
    }

    /**
     * @param string $className
     * @param string $scope
     *
     * @return string
     */
    private function getFieldConfigsCacheKey($className, $scope)
    {
        return $className . '.' . $scope;
    }

    private function getCacheKey(string $key): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey($key);
    }
}
