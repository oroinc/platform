<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\ORM\EntityManager;
use Metadata\MetadataFactory;
use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Exception\LogicException;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The main entry point for entity configs.
 * IMPORTANT: A performance of this class is very crucial. Double check a performance during a refactoring.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ConfigManager
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var MetadataFactory */
    protected $metadataFactory;

    /** @var ConfigCache */
    protected $cache;

    /** @var AuditManager */
    protected $auditManager;

    /** @var ConfigModelManager */
    protected $modelManager;

    /** @var ConfigProviderBag */
    private $providerBag;

    /** @var array */
    protected $originalValues = [];

    /** @var ConfigInterface[] */
    protected $persistConfigs = [];

    /** @var array */
    protected $configChangeSets = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param MetadataFactory          $metadataFactory
     * @param ConfigModelManager       $modelManager
     * @param AuditManager             $auditManager
     * @param ConfigCache              $cache
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        MetadataFactory $metadataFactory,
        ConfigModelManager $modelManager,
        AuditManager $auditManager,
        ConfigCache $cache
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->metadataFactory = $metadataFactory;
        $this->modelManager    = $modelManager;
        $this->auditManager    = $auditManager;
        $this->cache           = $cache;
    }

    /**
     * Gets the EntityManager responsible to work with configuration entities
     * IMPORTANT: configuration entities may use own entity manager which may be not equal the default entity manager
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->modelManager->getEntityManager();
    }

    /**
     * @return ConfigProvider[]
     */
    public function getProviders()
    {
        return $this->providerBag->getProviders();
    }

    /**
     * @param string $scope
     *
     * @return ConfigProvider|null
     */
    public function getProvider($scope)
    {
        return $this->providerBag->getProvider($scope);
    }

    /**
     * @param ConfigProviderBag $providerBag
     */
    public function setProviderBag(ConfigProviderBag $providerBag)
    {
        $this->providerBag = $providerBag;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Checks whether a database contains all tables required to work with entity configuration data.
     *
     * @return bool
     */
    public function isDatabaseReadyToWork()
    {
        return $this->modelManager->checkDatabase();
    }

    /**
     * @param string $className
     *
     * @return EntityMetadata|null
     */
    public function getEntityMetadata($className)
    {
        return class_exists($className)
            ? $this->metadataFactory->getMetadataForClass($className)
            : null;
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return FieldMetadata|null
     */
    public function getFieldMetadata($className, $fieldName)
    {
        $metadata = $this->getEntityMetadata($className);

        return $metadata && isset($metadata->propertyMetadata[$fieldName])
            ? $metadata->propertyMetadata[$fieldName]
            : null;
    }

    /**
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return int|null
     */
    public function getConfigModelId($className, $fieldName = null)
    {
        $item = $fieldName
            ? $this->findField($className, $fieldName)
            : $this->findEntity($className);

        return null !== $item
            ? $item['i']
            : null;
    }

    /**
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return bool|null
     */
    public function isHiddenModel($className, $fieldName = null)
    {
        $item = $fieldName
            ? $this->findField($className, $fieldName)
            : $this->findEntity($className);

        return null !== $item
            ? $item['h']
            : null;
    }

    /**
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return bool
     */
    public function hasConfig($className, $fieldName = null)
    {
        if (!$this->modelManager->checkDatabase()) {
            return false;
        }

        return $fieldName
            ? $this->isConfigurableField($className, $fieldName)
            : $this->isConfigurableEntity($className);
    }

    /**
     * @param ConfigIdInterface $configId
     *
     * @return ConfigInterface
     *
     * @throws RuntimeException
     */
    public function getConfig(ConfigIdInterface $configId)
    {
        $scope = $configId->getScope();
        if ($configId instanceof FieldConfigId) {
            return $this->getFieldConfig($scope, $configId->getClassName(), $configId->getFieldName());
        }

        $className = $configId->getClassName();
        if ($className) {
            return $this->getEntityConfig($scope, $className);
        }

        return $this->createEntityConfig($scope);
    }

    /**
     * @param string $scope
     *
     * @return ConfigInterface
     */
    public function createEntityConfig($scope)
    {
        return new Config(
            new EntityConfigId($scope),
            $this->getEntityDefaultValues($scope)
        );
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param string $scope
     * @return ConfigInterface
     */
    public function createFieldConfigByModel(FieldConfigModel $fieldConfigModel, string $scope)
    {
        return new Config(
            new FieldConfigId(
                $scope,
                $fieldConfigModel->getEntity()->getClassName(),
                $fieldConfigModel->getFieldName(),
                $fieldConfigModel->getType()
            ),
            $fieldConfigModel->toArray($scope)
        );
    }

    /**
     * @param string $scope
     * @param string $className
     *
     * @return ConfigInterface
     *
     * @throws RuntimeException
     */
    public function getEntityConfig($scope, $className)
    {
        $config = $this->cache->getEntityConfig($scope, $className);
        if (!$config) {
            if (!$this->modelManager->checkDatabase()) {
                throw $this->createDatabaseNotSyncedException();
            }

            if (!$this->isConfigurableEntity($className)) {
                throw new RuntimeException(sprintf('Entity "%s" is not configurable', $className));
            }

            $config = new Config(
                new EntityConfigId($scope, $className),
                $this->modelManager->getEntityModel($className)->toArray($scope)
            );

            // put to a cache
            $this->cache->saveConfig($config);
        }

        // for calculate change set
        $configKey = $scope . '.' . $className;
        if (!isset($this->originalValues[$configKey])) {
            $this->originalValues[$configKey] = $config->getValues();
        }

        return $config;
    }

    /**
     * @param string $scope
     * @param string $className
     * @param string $fieldName
     *
     * @return ConfigInterface
     *
     * @throws RuntimeException
     */
    public function getFieldConfig($scope, $className, $fieldName)
    {
        $config = $this->cache->getFieldConfig($scope, $className, $fieldName);
        if (!$config) {
            if (!$this->modelManager->checkDatabase()) {
                throw $this->createDatabaseNotSyncedException();
            }

            if (!$this->isConfigurableEntity($className)) {
                throw new RuntimeException(sprintf('Entity "%s" is not configurable', $className));
            }
            if (!$this->isConfigurableField($className, $fieldName)) {
                throw new RuntimeException(sprintf('Field "%s::%s" is not configurable', $className, $fieldName));
            }

            $model = $this->modelManager->getFieldModel($className, $fieldName);
            $config = new Config(
                new FieldConfigId($scope, $className, $fieldName, $model->getType()),
                $model->toArray($scope)
            );

            // put to a cache
            $this->cache->saveConfig($config);
        }

        // for calculate change set
        $configKey = $scope . '.' . $className . '.' . $fieldName;
        if (!isset($this->originalValues[$configKey])) {
            $this->originalValues[$configKey] = $config->getValues();
        }

        return $config;
    }

    /**
     * Gets configuration data for all configurable entities (if $className is not specified)
     * or all configurable fields of the given $className.
     *
     * @param string      $scope
     * @param string|null $className
     * @param bool        $withHidden Set true if you need ids of all configurable entities,
     *                                including entities marked as ConfigModel::MODE_HIDDEN
     *
     * @return ConfigInterface[]
     */
    public function getConfigs($scope, $className = null, $withHidden = false)
    {
        if ($className) {
            return $this->mapFields(
                function ($scope, $class, $field, $type) {
                    return $this->getFieldConfig($scope, $class, $field, $type);
                },
                $scope,
                $className,
                $withHidden
            );
        } else {
            return $this->mapEntities(
                function ($scope, $class) {
                    return $this->getEntityConfig($scope, $class);
                },
                $scope,
                $withHidden
            );
        }
    }

    /**
     * Gets a list of ids for all configurable entities (if $className is not specified)
     * or all configurable fields of the given $className.
     *
     * @param string $scope
     * @param string|null $className
     * @param bool $withHidden Set true if you need ids of all configurable entities,
     *                                including entities marked as ConfigModel::MODE_HIDDEN
     *
     * @return ConfigIdInterface[]
     */
    public function getIds($scope, $className = null, $withHidden = false)
    {
        if ($className) {
            return $this->mapFields(
                function ($scope, $class, $field, $type) {
                    return new FieldConfigId($scope, $class, $field, $type);
                },
                $scope,
                $className,
                $withHidden
            );
        } else {
            return $this->mapEntities(
                function ($scope, $class) {
                    return new EntityConfigId($scope, $class);
                },
                $scope,
                $withHidden
            );
        }
    }

    /**
     * @param string      $scope
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return ConfigIdInterface
     */
    public function getId($scope, $className, $fieldName = null)
    {
        if ($fieldName) {
            $field = $this->findField($className, $fieldName);
            if (null === $field) {
                throw new RuntimeException(sprintf('Field "%s::%s" is not configurable', $className, $fieldName));
            }

            return new FieldConfigId($scope, $className, $fieldName, $field['t']);
        } else {
            return new EntityConfigId($scope, $className);
        }
    }

    /**
     * Discards all unsaved changes and clears all related caches.
     */
    public function clear()
    {
        $this->clearCache();
        $this->clearConfigurableCache();

        $this->modelManager->clearCache();
        $this->getEntityManager()->clear();
    }

    /**
     * Removes all configuration data or for the given object (if $configId is specified) from a cache.
     *
     * @param ConfigIdInterface|null $configId
     */
    public function clearCache(ConfigIdInterface $configId = null)
    {
        if ($configId) {
            if ($configId instanceof FieldConfigId) {
                $this->cache->deleteFieldConfig($configId->getClassName(), $configId->getFieldName());
            } else {
                $this->cache->deleteEntityConfig($configId->getClassName());
            }
        } else {
            $this->cache->deleteAllConfigs();
        }
    }

    /**
     * Clears a cache of configurable entity flags
     */
    public function clearConfigurableCache()
    {
        $this->modelManager->clearCheckDatabase();
        $this->cache->deleteAllConfigurable();
    }

    /**
     * Clears entity config model cache.
     */
    public function clearModelCache()
    {
        $this->modelManager->clearCache();
        $this->cache->deleteAllConfigurable(true);
        $this->cache->deleteAllConfigs(true);
    }

    /**
     * Removes all entries from all used caches, completely.
     */
    public function flushAllCaches()
    {
        /**
         * The Doctrine cache provider has two methods to clear the cache:
         *  deleteAll - Deletes all cache entries in the current cache namespace.
         *  flushAll - Flushes all cache entries, globally.
         * Actually deleteAll method does not remove cached entries, it just increase cache version. The flushAll
         * deletes all cached entries, but it does it for all namespaces.
         * The problem is that we use the same cache, but for different caches we use different namespaces.
         * E.g. we use oro_entity_aliases namespace for entity alias cache and oro_entity_config namespace for
         * entity config cache. But if a developer call flushAll method for any of these cache all cached entries
         * from all caches will be removed
         */
        $this->cache->deleteAllConfigurable();
        $this->cache->deleteAllConfigs();
    }

    /**
     * Makes the given configuration object managed and persistent.
     *
     * @param ConfigInterface $config
     */
    public function persist(ConfigInterface $config)
    {
        $configKey = $this->buildConfigKey($config->getId());

        $this->persistConfigs[$configKey] = $config;
    }

    /**
     * Merges configuration data from the given configuration object with existing
     * managed configuration object.
     *
     * @param ConfigInterface $config
     */
    public function merge(ConfigInterface $config)
    {
        $this->mergeConfigValues($config, $this->buildConfigKey($config->getId()));
    }

    /**
     * Flushes all changes that have been queued up to now to a database.
     */
    public function flush()
    {
        /** @var ConfigModel[] $models */
        $models = [];
        $this->prepareFlush($models);

        $em  = $this->getEntityManager();
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $logEntry = $this->auditManager->buildEntity($this);

        foreach ($models as $model) {
            if (null === $model->getId()) {
                $model->setCreated($now);
            }
            $model->setUpdated($now);
            $em->persist($model);
        }

        if (!empty($models)) {
            $em->flush();
        }

        if (null !== $logEntry) {
            $this->auditManager->save($logEntry);
        }

        $this->eventDispatcher->dispatch(
            Events::POST_FLUSH,
            new Event\PostFlushConfigEvent($models, $this)
        );

        if (!empty($models)) {
            $this->cache->deleteAllConfigurable();
        }

        $this->persistConfigs   = [];
        $this->configChangeSets = [];
    }

    /**
     * @param array $models
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function prepareFlush(&$models)
    {
        $groupedConfigs = [];
        $persistConfigsCount = count($this->persistConfigs);

        foreach ($this->persistConfigs as $config) {
            $this->calculateConfigChangeSet($config);

            $configId = $config->getId();
            $modelKey = $configId instanceof FieldConfigId
                ? $configId->getClassName() . '.' . $configId->getFieldName()
                : $configId->getClassName();

            $groupedConfigs[$modelKey][$configId->getScope()] = $config;
        }

        /** @var ConfigInterface[] $configs */
        foreach ($groupedConfigs as $modelKey => $configs) {
            $this->eventDispatcher->dispatch(
                Events::PRE_FLUSH,
                new Event\PreFlushConfigEvent($configs, $this)
            );
            foreach ($configs as $scope => $config) {
                $configId  = $config->getId();
                $className = $configId->getClassName();
                $fieldName = $configId instanceof FieldConfigId
                    ? $configId->getFieldName()
                    : null;

                if (isset($models[$modelKey])) {
                    $model = $models[$modelKey];
                } else {
                    $model             = null !== $fieldName
                        ? $this->modelManager->getFieldModel($className, $fieldName)
                        : $this->modelManager->getEntityModel($className);
                    $models[$modelKey] = $model;
                }

                $indexedValues = $this->getPropertyConfig($scope)->getIndexedValues(
                    null !== $fieldName ? PropertyConfigContainer::TYPE_FIELD : PropertyConfigContainer::TYPE_ENTITY
                );
                $model->fromArray($scope, $config->getValues(), $indexedValues);

                if (null !== $fieldName) {
                    $this->cache->deleteFieldConfig($className, $fieldName);
                } else {
                    $this->cache->deleteEntityConfig($className);
                }
            }
        }

        // First we are comparing persistConfigs size to configChangeSets size in case one of them was changed.
        // Then we are comparing persistConfigs size to stored persistConfigs size in case new persistConfigs
        // were added and configChangeSets were recalculated. In this case persistConfigs and configChangeSets sizes
        // will be equal, but we still need to rerun the logic with updated persistConfigs.
        if (count($this->persistConfigs) !== count($this->configChangeSets)
            || count($this->persistConfigs) !== $persistConfigsCount
        ) {
            $this->prepareFlush($models);
        }
    }

    /**
     * @param ConfigInterface $config
     */
    public function calculateConfigChangeSet(ConfigInterface $config)
    {
        $configKey = $this->buildConfigKey($config->getId());

        $diff = isset($this->originalValues[$configKey])
            ? $this->getDiff($config->getValues(), $this->originalValues[$configKey])
            : $this->getDiff($config->getValues(), []);
        if (!empty($diff)) {
            $this->configChangeSets[$configKey] = isset($this->configChangeSets[$configKey])
                ? array_merge($this->configChangeSets[$configKey], $diff)
                : $diff;
        } elseif (!isset($this->configChangeSets[$configKey])) {
            $this->configChangeSets[$configKey] = [];
        }
    }

    /**
     * @return ConfigInterface[]
     */
    public function getUpdateConfig()
    {
        return array_values($this->persistConfigs);
    }

    /**
     * @param ConfigInterface $config
     *
     * @return array [old_value, new_value] or empty array
     */
    public function getConfigChangeSet(ConfigInterface $config)
    {
        $configKey = $this->buildConfigKey($config->getId());

        return $this->configChangeSets[$configKey] ?? [];
    }

    /**
     * @param string $scope
     * @param string $className
     *
     * @return array [old_value, new_value] or empty array
     */
    public function getEntityConfigChangeSet($scope, $className)
    {
        $configKey = $this->buildEntityConfigKey($scope, $className);

        return $this->configChangeSets[$configKey] ?? [];
    }

    /**
     * @param string $scope
     * @param string $className
     * @param string $fieldName
     *
     * @return array [old_value, new_value] or empty array
     */
    public function getFieldConfigChangeSet($scope, $className, $fieldName)
    {
        $configKey = $this->buildFieldConfigKey($scope, $className, $fieldName);

        return $this->configChangeSets[$configKey] ?? [];
    }

    /**
     * Checks if the configuration model for the given class exists
     *
     * @param string $className
     *
     * @return bool
     */
    public function hasConfigEntityModel($className)
    {
        return null !== $this->modelManager->findEntityModel($className);
    }

    /**
     * Checks if the configuration model for the given field exist
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasConfigFieldModel($className, $fieldName)
    {
        return null !== $this->modelManager->findFieldModel($className, $fieldName);
    }

    /**
     * Gets a config model for the given entity
     *
     * @param string $className
     *
     * @return EntityConfigModel|null
     */
    public function getConfigEntityModel($className)
    {
        return $this->modelManager->findEntityModel($className);
    }

    /**
     * Gets a config model for the given entity field
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return FieldConfigModel|null
     */
    public function getConfigFieldModel($className, $fieldName)
    {
        return $this->modelManager->findFieldModel($className, $fieldName);
    }

    /**
     * @param string|null $className
     * @param string|null $mode
     *
     * @return EntityConfigModel
     */
    public function createConfigEntityModel($className = null, $mode = null)
    {
        if (empty($className)) {
            $entityModel = $this->modelManager->createEntityModel(
                $className,
                $mode ?: ConfigModel::MODE_DEFAULT
            );
        } else {
            $entityModel = $this->modelManager->findEntityModel($className);
            if (null === $entityModel) {
                $metadata = $this->getEntityMetadata($className);
                if (!$mode) {
                    $mode = $metadata && $metadata->mode
                        ? $metadata->mode
                        : ConfigModel::MODE_DEFAULT;
                }
                $entityModel = $this->modelManager->createEntityModel($className, $mode);
                $providers = $this->getProviders();
                foreach ($providers as $scope => $provider) {
                    $configKey = $scope . '.' . $className;
                    $config    = new Config(
                        new EntityConfigId($scope, $className),
                        $this->getEntityDefaultValues($scope, $className, $metadata)
                    );
                    $this->mergeConfigValues($config, $configKey);

                    // put a config to a local cache
                    $this->cache->saveConfig($config, true);
                    // for calculate change set
                    if (!isset($this->originalValues[$configKey])) {
                        $this->originalValues[$configKey] = $config->getValues();
                    }
                }
                // put "configurable" flag to a local cache
                $this->cache->saveConfigurable(true, $className, null, true);
                // if needed, update the list of entities in a local cache
                $entities = $this->cache->getEntities(true);
                if (null !== $entities && !isset($entities[$className])) {
                    $entities[$className] = [
                        'i' => null,
                        'h' => $mode === ConfigModel::MODE_HIDDEN
                    ];
                    $this->cache->saveEntities($entities, true);
                }

                $this->eventDispatcher->dispatch(
                    Events::CREATE_ENTITY,
                    new Event\EntityConfigEvent($className, $this)
                );
            }
        }

        return $entityModel;
    }

    /**
     * @param string      $className
     * @param string      $fieldName
     * @param string      $fieldType
     * @param string|null $mode
     *
     * @return FieldConfigModel
     */
    public function createConfigFieldModel($className, $fieldName, $fieldType, $mode = null)
    {
        $fieldModel = $this->modelManager->findFieldModel($className, $fieldName);
        if (null === $fieldModel) {
            $metadata = $this->getFieldMetadata($className, $fieldName);
            if (!$mode) {
                $mode = $metadata && $metadata->mode
                    ? $metadata->mode
                    : ConfigModel::MODE_DEFAULT;
            }
            $fieldModel = $this->modelManager->createFieldModel($className, $fieldName, $fieldType, $mode);
            $providers = $this->getProviders();
            foreach ($providers as $scope => $provider) {
                $configKey = $scope . '.' . $className . '.' . $fieldName;
                $config    = new Config(
                    new FieldConfigId($scope, $className, $fieldName, $fieldType),
                    $this->getFieldDefaultValues($scope, $className, $fieldName, $fieldType, $metadata)
                );
                $this->mergeConfigValues($config, $configKey);

                // put a config to a local cache
                $this->cache->saveConfig($config, true);
                // for calculate change set
                if (!isset($this->originalValues[$configKey])) {
                    $this->originalValues[$configKey] = $config->getValues();
                }
            }
            // put "configurable" flag to a local cache
            $this->cache->saveConfigurable(true, $className, $fieldName, true);
            // if needed, update the list of fields in a local cache
            $fields = $this->cache->getFields($className, true);
            if (null !== $fields && !isset($fields[$fieldName])) {
                $fields[$fieldName] = [
                    'i' => null,
                    'h' => $mode === ConfigModel::MODE_HIDDEN,
                    't' => $fieldType,
                ];
                $this->cache->saveFields($className, $fields, true);
            }

            $this->eventDispatcher->dispatch(
                Events::CREATE_FIELD,
                new Event\FieldConfigEvent($className, $fieldName, $this)
            );
        }

        return $fieldModel;
    }

    /**
     * @param string $className
     * @param bool   $force - if TRUE overwrite existing value from annotation
     */
    public function updateConfigEntityModel($className, $force = false)
    {
        // existing values for a custom entity must not be overridden
        if ($force && $this->isCustom($className)) {
            $force = false;
        }

        $metadata = $this->getEntityMetadata($className);
        $entityModel = $this->createConfigEntityModel($className, $metadata->mode);
        $entityModel->setMode($metadata->mode);
        $providers = $this->getProviders();
        foreach ($providers as $scope => $provider) {
            $config        = $provider->getConfig($className);
            $defaultValues = $this->getEntityDefaultValues($scope, $className, $metadata);
            $hasChanges    = $this->updateConfigValues($config, $defaultValues, $force);
            if ($hasChanges) {
                $configKey = $scope . '.' . $className;

                $this->persistConfigs[$configKey] = $config;
            }
        }

        $this->eventDispatcher->dispatch(
            Events::UPDATE_ENTITY,
            new Event\EntityConfigEvent($className, $this)
        );
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param bool   $force - if TRUE overwrite existing value from annotation
     */
    public function updateConfigFieldModel($className, $fieldName, $force = false)
    {
        // existing values for a custom field must not be overridden
        if ($force && $this->isCustom($className, $fieldName)) {
            $force = false;
        }

        $metadata = $this->getFieldMetadata($className, $fieldName);
        $providers = $this->getProviders();
        foreach ($providers as $scope => $provider) {
            $config = $provider->getConfig($className, $fieldName);
            /** @var FieldConfigId $configId */
            $configId      = $config->getId();
            $defaultValues = $this->getFieldDefaultValues(
                $scope,
                $className,
                $fieldName,
                $configId->getFieldType(),
                $metadata
            );
            $hasChanges    = $this->updateConfigValues($config, $defaultValues, $force);
            if ($hasChanges) {
                $configKey = $scope . '.' . $className . '.' . $fieldName;

                $this->persistConfigs[$configKey] = $config;
            }
        }

        $this->eventDispatcher->dispatch(
            Events::UPDATE_FIELD,
            new Event\FieldConfigEvent($className, $fieldName, $this)
        );
    }

    /**
     * Changes a type of a field
     *
     * @param string $className
     * @param string $fieldName
     * @param string $newFieldName
     *
     * @return bool TRUE if the name was changed; otherwise, FALSE
     */
    public function changeFieldName($className, $fieldName, $newFieldName)
    {
        $result = $this->modelManager->changeFieldName($className, $fieldName, $newFieldName);
        if ($result) {
            $this->eventDispatcher->dispatch(
                Events::RENAME_FIELD,
                new Event\RenameFieldEvent($className, $fieldName, $newFieldName, $this)
            );
            $providers = $this->getProviders();
            foreach ($providers as $scope => $provider) {
                $cachedConfig = $this->cache->getFieldConfig($scope, $className, $fieldName, true);
                if ($cachedConfig) {
                    $this->cache->saveConfig($this->changeConfigFieldName($cachedConfig, $newFieldName), true);
                    $this->cache->deleteFieldConfig($className, $fieldName, true);
                }

                $newConfigKey = $scope . '.' . $className . '.' . $newFieldName;
                $configKey    = $scope . '.' . $className . '.' . $fieldName;
                if (isset($this->persistConfigs[$configKey])) {
                    $this->persistConfigs[$newConfigKey] = $this->changeConfigFieldName(
                        $this->persistConfigs[$configKey],
                        $newFieldName
                    );
                    unset($this->persistConfigs[$configKey]);
                }
                if (isset($this->originalValues[$configKey])) {
                    $this->originalValues[$newConfigKey] = $this->originalValues[$configKey];
                    unset($this->originalValues[$configKey]);
                }
                if (isset($this->configChangeSets[$configKey])) {
                    $this->configChangeSets[$newConfigKey] = $this->configChangeSets[$configKey];
                    unset($this->configChangeSets[$configKey]);
                }
            }
        };

        return $result;
    }

    /**
     * Changes a type of a field
     *
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     *
     * @return bool TRUE if the type was changed; otherwise, FALSE
     */
    public function changeFieldType($className, $fieldName, $fieldType)
    {
        return $this->modelManager->changeFieldType($className, $fieldName, $fieldType);
    }

    /**
     * Changes a mode of a field
     *
     * @param string $className
     * @param string $fieldName
     * @param string $mode      Can be the value of one of ConfigModel::MODE_* constants
     *
     * @return bool TRUE if the mode was changed; otherwise, FALSE
     */
    public function changeFieldMode($className, $fieldName, $mode)
    {
        return $this->modelManager->changeFieldMode($className, $fieldName, $mode);
    }

    /**
     * Changes a mode of an entity
     *
     * @param string $className
     * @param string $mode      Can be the value of one of ConfigModel::MODE_* constants
     *
     * @return bool TRUE if the type was changed; otherwise, FALSE
     */
    public function changeEntityMode($className, $mode)
    {
        return $this->modelManager->changeEntityMode($className, $mode);
    }

    /**
     * Gets config id for the given model
     *
     * @param ConfigModel $model
     * @param string      $scope
     *
     * @return ConfigIdInterface
     */
    public function getConfigIdByModel($model, $scope)
    {
        if ($model instanceof FieldConfigModel) {
            return new FieldConfigId(
                $scope,
                $model->getEntity()->getClassName(),
                $model->getFieldName(),
                $model->getType()
            );
        } else {
            return new EntityConfigId($scope, $model->getClassName());
        }
    }

    /**
     * Gets a model for the given config id
     *
     * @param ConfigIdInterface $configId
     *
     * @return ConfigModel
     */
    protected function getModelByConfigId(ConfigIdInterface $configId)
    {
        return $configId instanceof FieldConfigId
            ? $this->modelManager->getFieldModel($configId->getClassName(), $configId->getFieldName())
            : $this->modelManager->getEntityModel($configId->getClassName());
    }

    /**
     * In case of FieldConfigId replaces OLD field name with given NEW one
     *
     * @param ConfigInterface $config
     * @param string          $newFieldName
     *
     * @return ConfigInterface
     */
    protected function changeConfigFieldName(ConfigInterface $config, $newFieldName)
    {
        $configId = $config->getId();
        if ($configId instanceof FieldConfigId) {
            $newConfigId = new FieldConfigId(
                $configId->getScope(),
                $configId->getClassName(),
                $newFieldName,
                $configId->getFieldType()
            );

            $config = new Config($newConfigId, $config->getValues());
        }

        return $config;
    }

    /**
     * Extracts entity default values from an annotation and config file
     *
     * @param string              $scope
     * @param string|null         $className
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function getEntityDefaultValues($scope, $className = null, $metadata = null)
    {
        $propertyConfig = $this->getPropertyConfig($scope);

        // try to get default values from an annotation
        if ($metadata && isset($metadata->defaultValues[$scope])) {
            // combine them with default values from a config file
            $defaultValues = array_merge(
                $propertyConfig->getDefaultValues(PropertyConfigContainer::TYPE_ENTITY),
                $metadata->defaultValues[$scope]
            );
        } else {
            $defaultValues = $propertyConfig->getDefaultValues(PropertyConfigContainer::TYPE_ENTITY);
        }

        // process translatable values
        if ($className) {
            $translatablePropertyNames = $propertyConfig->getTranslatableValues(PropertyConfigContainer::TYPE_ENTITY);
            foreach ($translatablePropertyNames as $propertyName) {
                if (empty($defaultValues[$propertyName])) {
                    $defaultValues[$propertyName] =
                        ConfigHelper::getTranslationKey($scope, $propertyName, $className);
                }
            }
        }

        return $defaultValues;
    }

    /**
     * Extracts field default values from an annotation and config file
     *
     * @param string             $scope
     * @param string             $className
     * @param string             $fieldName
     * @param string             $fieldType
     * @param FieldMetadata|null $metadata
     *
     * @return array
     */
    protected function getFieldDefaultValues($scope, $className, $fieldName, $fieldType, $metadata = null)
    {
        $propertyConfig = $this->getPropertyConfig($scope);

        // try to get default values from an annotation
        if ($metadata && isset($metadata->defaultValues[$scope])) {
            // combine them with default values from a config file
            $defaultValues = array_merge(
                $propertyConfig->getDefaultValues(PropertyConfigContainer::TYPE_FIELD, $fieldType),
                $metadata->defaultValues[$scope]
            );
        } else {
            $defaultValues = $propertyConfig->getDefaultValues(PropertyConfigContainer::TYPE_FIELD, $fieldType);
        }

        // process translatable values
        $translatablePropertyNames = $propertyConfig->getTranslatableValues(PropertyConfigContainer::TYPE_FIELD);
        foreach ($translatablePropertyNames as $propertyName) {
            if (empty($defaultValues[$propertyName])) {
                $defaultValues[$propertyName] =
                    ConfigHelper::getTranslationKey($scope, $propertyName, $className, $fieldName);
            }
        }

        return $defaultValues;
    }

    /**
     * Updates values of the given config based on the given default values and $force flag
     *
     * @param ConfigInterface $config
     * @param array           $defaultValues
     * @param bool            $force
     *
     * @return bool  TRUE if at least one config value was updated; otherwise, FALSE
     */
    protected function updateConfigValues(ConfigInterface $config, array $defaultValues, $force)
    {
        $hasChanges = false;
        foreach ($defaultValues as $code => $value) {
            if (!$config->has($code) || $force) {
                $config->set($code, $value);
                $hasChanges = true;
            }
        }

        return $hasChanges;
    }

    /**
     * @param ConfigInterface $config
     * @param string          $configKey
     */
    protected function mergeConfigValues(ConfigInterface $config, $configKey)
    {
        if (isset($this->persistConfigs[$configKey])) {
            $existingValues = $this->persistConfigs[$configKey]->getValues();
            if (!empty($existingValues)) {
                $config->setValues(array_merge($existingValues, $config->getValues()));
            }
        }
        $this->persistConfigs[$configKey] = $config;
    }

    /**
     * Computes the difference of current and original config values
     *
     * @param array $values
     * @param array $originalValues
     *
     * @return array
     */
    protected function getDiff($values, $originalValues)
    {
        $diff = [];
        if (empty($originalValues)) {
            foreach ($values as $code => $value) {
                $diff[$code] = [null, $value];
            }
        } else {
            foreach ($originalValues as $code => $originalValue) {
                if (array_key_exists($code, $values)) {
                    $value = $values[$code];
                    if ($originalValue != $value) {
                        $diff[$code] = [$originalValue, $value];
                    }
                }
            }
            foreach ($values as $code => $value) {
                if (!array_key_exists($code, $originalValues)) {
                    $diff[$code] = [null, $value];
                }
            }
        }

        return $diff;
    }

    /**
     * Returns a string unique identifies each config item
     *
     * @param ConfigIdInterface $configId
     *
     * @return string
     */
    protected function buildConfigKey(ConfigIdInterface $configId)
    {
        return $configId instanceof FieldConfigId
            ? $this->buildFieldConfigKey($configId->getScope(), $configId->getClassName(), $configId->getFieldName())
            : $this->buildEntityConfigKey($configId->getScope(), $configId->getClassName());
    }

    /**
     * Returns a string unique identifies each entity config item
     *
     * @param string $scope
     * @param string $className
     *
     * @return string
     */
    protected function buildEntityConfigKey($scope, $className)
    {
        return $scope . '.' . $className;
    }

    /**
     * Returns a string unique identifies each field config item
     *
     * @param string $scope
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     */
    protected function buildFieldConfigKey($scope, $className, $fieldName)
    {
        return $scope . '.' . $className . '.' . $fieldName;
    }

    /**
     * @param string $scope
     *
     * @return PropertyConfigContainer
     */
    protected function getPropertyConfig($scope)
    {
        return $this->providerBag->getProvider($scope)->getPropertyConfig();
    }

    /**
     * @param callable $callback
     * @param string   $scope
     * @param bool     $withHidden
     *
     * @return array
     */
    protected function mapEntities($callback, $scope, $withHidden)
    {
        $result = [];

        $entities = $this->cache->getEntities();
        if (null !== $entities) {
            foreach ($entities as $class => $data) {
                if ($withHidden || !$data['h']) {
                    $result[] = $callback($scope, $class);
                }
            }
        } elseif ($this->modelManager->checkDatabase()) {
            $models   = $this->modelManager->getModels();
            $entities = [];
            /** @var EntityConfigModel $model */
            foreach ($models as $model) {
                $isHidden = $model->isHidden();
                $class    = $model->getClassName();

                $entities[$class] = [
                    'i' => $model->getId(),
                    'h' => $isHidden
                ];
                if ($withHidden || !$isHidden) {
                    $result[] = $callback($scope, $class);
                }
            }
            $this->cache->saveEntities($entities);
        }

        return $result;
    }

    /**
     * @param string $className
     *
     * @return array|null ['i' => entity_model_id, 'h' => is_hidden_model]
     */
    protected function findEntity($className)
    {
        $result = null;

        $entities = $this->cache->getEntities();
        if (null === $entities && $this->modelManager->checkDatabase()) {
            $models   = $this->modelManager->getModels();
            $entities = [];
            /** @var EntityConfigModel $model */
            foreach ($models as $model) {
                $isHidden = $model->isHidden();
                $class    = $model->getClassName();

                $entities[$class] = [
                    'i' => $model->getId(),
                    'h' => $isHidden
                ];
            }
            $this->cache->saveEntities($entities);
        }
        if (null !== $entities && isset($entities[$className])) {
            $result = $entities[$className];
        }

        return $result;
    }

    /**
     * @param callable $callback
     * @param string   $scope
     * @param string   $className
     * @param bool     $withHidden
     *
     * @return array
     */
    protected function mapFields($callback, $scope, $className, $withHidden)
    {
        $result = [];

        $fields = $this->cache->getFields($className);
        if (null !== $fields) {
            foreach ($fields as $field => $data) {
                if ($withHidden || !$data['h']) {
                    $result[] = $callback($scope, $className, $field, $data['t']);
                }
            }
        } elseif ($this->modelManager->checkDatabase()) {
            $models = $this->modelManager->getModels($className);
            $fields = [];
            /** @var FieldConfigModel $model */
            foreach ($models as $model) {
                $isHidden = $model->isHidden();
                $field    = $model->getFieldName();
                $type     = $model->getType();

                $fields[$field] = [
                    'i' => $model->getId(),
                    'h' => $isHidden,
                    't' => $type,
                ];
                if ($withHidden || !$isHidden) {
                    $result[] = $callback($scope, $className, $field, $type);
                }
            }
            $this->cache->saveFields($className, $fields);
        }

        return $result;
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return array|null ['i' => field_model_id, 'h' => is_hidden_model, 't' => field_type]
     */
    protected function findField($className, $fieldName)
    {
        $result = null;

        $fields = $this->cache->getFields($className);
        if (null === $fields && $this->modelManager->checkDatabase()) {
            $models = $this->modelManager->getModels($className);
            $fields = [];
            /** @var FieldConfigModel $model */
            foreach ($models as $model) {
                $isHidden = $model->isHidden();
                $field    = $model->getFieldName();
                $type     = $model->getType();

                $fields[$field] = [
                    'i' => $model->getId(),
                    'h' => $isHidden,
                    't' => $type,
                ];
            }
            $this->cache->saveFields($className, $fields);
        }
        if (null !== $fields && isset($fields[$fieldName])) {
            $result = $fields[$fieldName];
        }

        return $result;
    }

    /**
     * Checks whether an entity is configurable.
     *
     * @param string $className
     *
     * @return bool
     */
    protected function isConfigurableEntity($className)
    {
        $isConfigurable = $this->cache->getConfigurable($className);
        if (null === $isConfigurable) {
            $isConfigurable = (null !== $this->modelManager->findEntityModel($className));
            $this->cache->saveConfigurable($isConfigurable, $className);
        }

        return $isConfigurable;
    }

    /**
     * Checks whether a field is configurable.
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isConfigurableField($className, $fieldName)
    {
        $isConfigurable = $this->cache->getConfigurable($className, $fieldName);
        if (null === $isConfigurable) {
            $isConfigurable = (null !== $this->modelManager->findFieldModel($className, $fieldName));
            $this->cache->saveConfigurable($isConfigurable, $className, $fieldName);
        }

        return $isConfigurable;
    }

    /**
     * Checks whether an entity or entity field is custom or system
     * Custom means that "extend::owner" equals "Custom"
     *
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return bool
     */
    protected function isCustom($className, $fieldName = null)
    {
        $result         = false;
        $extendProvider = $this->getProvider('extend');
        if ($extendProvider && $extendProvider->hasConfig($className, $fieldName)) {
            $result = $extendProvider->getConfig($className, $fieldName)
                ->is('owner', ExtendScope::OWNER_CUSTOM);
        }

        return $result;
    }

    /**
     * @return LogicException
     */
    protected function createDatabaseNotSyncedException()
    {
        return new LogicException(
            'Database is not synced, if you use ConfigManager, when a db schema may be hasn\'t synced.'
            . ' check it by ConfigManager::modelManager::checkDatabase'
        );
    }
}
