<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\ORM\EntityManager;

use Metadata\MetadataFactory;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Exception\LogicException;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * IMPORTANT: A performance of this class is very crucial. Double check a performance during a refactoring.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ConfigManager
{
    /** @var MetadataFactory */
    protected $metadataFactory;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var ConfigCache */
    protected $cache;

    /** @var AuditEntityBuilder */
    protected $auditEntityBuilder;

    /** @var ConfigModelManager */
    protected $modelManager;

    /** @var ConfigProvider[] */
    protected $providers = [];

    /** @var PropertyConfigContainer[] */
    protected $propertyConfigs = [];

    /** @var array */
    protected $originalValues = [];

    /** @var ConfigInterface[] */
    protected $persistConfigs = [];

    /** @var array */
    protected $configChangeSets = [];

    /**
     * @deprecated since 1.9. Should be removed together with deprecated events
     * @see Oro\Bundle\EntityConfigBundle\Event\Events
     * @var array
     */
    private $hasListenersCache = [];

    /**
     * @param MetadataFactory          $metadataFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param ConfigModelManager       $modelManager
     * @param AuditEntityBuilder       $auditEntityBuilder
     * @param ConfigCache              $cache
     */
    public function __construct(
        MetadataFactory $metadataFactory,
        EventDispatcherInterface $eventDispatcher,
        ConfigModelManager $modelManager,
        AuditEntityBuilder $auditEntityBuilder,
        ConfigCache $cache
    ) {
        $this->metadataFactory    = $metadataFactory;
        $this->eventDispatcher    = $eventDispatcher;
        $this->modelManager       = $modelManager;
        $this->auditEntityBuilder = $auditEntityBuilder;
        $this->cache              = $cache;
    }

    /**
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
        return $this->providers;
    }

    /**
     * @param string $scope
     *
     * @return ConfigProvider|null
     */
    public function getProvider($scope)
    {
        return isset($this->providers[$scope]) ? $this->providers[$scope] : null;
    }

    /**
     * @param ConfigProvider $provider
     */
    public function addProvider(ConfigProvider $provider)
    {
        $this->providers[$provider->getScope()] = $provider;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
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
     * @return bool
     */
    public function hasConfig($className, $fieldName = null)
    {
        if (!$this->modelManager->checkDatabase()) {
            return false;
        }

        $result = $this->cache->getConfigurable($className, $fieldName);
        if (null === $result) {
            $model  = $fieldName
                ? $this->modelManager->findFieldModel($className, $fieldName)
                : $this->modelManager->findEntityModel($className);
            $result = (null !== $model);

            $this->cache->saveConfigurable($result, $className, $fieldName);
        }

        return $result;
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

            $isConfigurableEntity = $this->cache->getConfigurable($className);
            if (null === $isConfigurableEntity) {
                $isConfigurableEntity = (null !== $this->modelManager->findEntityModel($className));
                $this->cache->saveConfigurable($isConfigurableEntity, $className);
            }
            if (!$isConfigurableEntity) {
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

            $isConfigurableEntity = $this->cache->getConfigurable($className);
            if (null === $isConfigurableEntity) {
                $isConfigurableEntity = (null !== $this->modelManager->findEntityModel($className));
                $this->cache->saveConfigurable($isConfigurableEntity, $className);
            }
            if (!$isConfigurableEntity) {
                throw new RuntimeException(sprintf('Entity "%s" is not configurable', $className));
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
     *                                including entities marked as mode="hidden"
     *
     * @return ConfigInterface[]
     */
    public function getConfigs($scope, $className = null, $withHidden = false)
    {
        if (!$this->modelManager->checkDatabase()) {
            return [];
        }

        $models = $this->modelManager->getModels($className, $withHidden);

        $configs = [];
        if ($className) {
            /** @var FieldConfigModel $model */
            foreach ($models as $model) {
                $configs[] = $this->getFieldConfig($scope, $className, $model->getFieldName(), $model->getType());
            }
        } else {
            /** @var EntityConfigModel $model */
            foreach ($models as $model) {
                $configs[] = $this->getEntityConfig($scope, $model->getClassName());
            }
        }

        return $configs;
    }

    /**
     * Gets a list of ids for all configurable entities (if $className is not specified)
     * or all configurable fields of the given $className.
     *
     * @param string $scope
     * @param string|null $className
     * @param bool $withHidden Set true if you need ids of all configurable entities,
     *                                including entities marked as mode="hidden"
     *
     * @return array
     */
    public function getIds($scope, $className = null, $withHidden = false)
    {
        if (!$this->modelManager->checkDatabase()) {
            return [];
        }

        $models = $this->modelManager->getModels($className, $withHidden);

        $ids = [];
        if ($className) {
            /** @var FieldConfigModel $model */
            foreach ($models as $model) {
                $ids[] = new FieldConfigId($scope, $className, $model->getFieldName(), $model->getType());
            }
        } else {
            /** @var EntityConfigModel $model */
            foreach ($models as $model) {
                $ids[] = new EntityConfigId($scope, $model->getClassName());
            }
        }

        return $ids;
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
            return $this->getFieldConfig($scope, $className, $fieldName)->getId();
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
     * Clears entity config cache
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
    }

    /**
     * @param ConfigInterface $config
     */
    public function persist(ConfigInterface $config)
    {
        $configKey = $this->buildConfigKey($config->getId());

        $this->persistConfigs[$configKey] = $config;
    }

    /**
     * @param ConfigInterface $config
     */
    public function merge(ConfigInterface $config)
    {
        $this->mergeConfigValues($config, $this->buildConfigKey($config->getId()));
    }

    public function flush()
    {
        /** @var AbstractConfigModel[] $models */
        $models = [];
        $this->prepareFlush($models);

        $em  = $this->getEntityManager();
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $logEntry = $this->auditEntityBuilder->buildEntity($this);
        if (null !== $logEntry) {
            $logEntry->setLoggedAt($now);
            $em->persist($logEntry);
        }

        foreach ($models as $model) {
            if (null === $model->getId()) {
                $model->setCreated($now);
            }
            $model->setUpdated($now);
            $em->persist($model);
        }

        // @todo: need investigation if we can call this flush only if !empty($models)
        $em->flush();

        // @todo: Should be removed together with deprecated events
        if ($this->hasListeners(Events::POST_FLUSH_CONFIG)) {
            $this->eventDispatcher->dispatch(
                Events::POST_FLUSH_CONFIG,
                new Event\FlushConfigEvent($models, $this)
            );
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
        foreach ($this->persistConfigs as $config) {
            $this->calculateConfigChangeSet($config);

            // @todo: Should be removed together with deprecated events
            if ($this->hasListeners(Events::PRE_PERSIST_CONFIG)) {
                $this->eventDispatcher->dispatch(
                    Events::PRE_PERSIST_CONFIG,
                    new Event\PersistConfigEvent($config, $this)
                );
            }

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

        if (count($this->persistConfigs) !== count($this->configChangeSets)) {
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

        return isset($this->configChangeSets[$configKey])
            ? $this->configChangeSets[$configKey]
            : [];
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
    public function createConfigEntityModel($className = null, $mode = ConfigModelManager::MODE_DEFAULT)
    {
        if (empty($className)) {
            $entityModel = $this->modelManager->createEntityModel($className, $mode);
        } else {
            $entityModel = $this->modelManager->findEntityModel($className);
            if (null === $entityModel) {
                $metadata      = $this->getEntityMetadata($className);
                $newEntityMode = $metadata ? $metadata->mode : $mode;
                $entityModel   = $this->modelManager->createEntityModel($className, $newEntityMode);
                foreach ($this->providers as $scope => $provider) {
                    $configKey = $scope . '.' . $className;
                    $config    = new Config(
                        new EntityConfigId($scope, $className),
                        $this->getEntityDefaultValues($scope, $className, $metadata)
                    );
                    $this->mergeConfigValues($config, $configKey);

                    // local cache
                    $this->cache->saveConfig($config, true);
                    $this->cache->saveConfigurable(true, $className, null, true);
                    // for calculate change set
                    if (!isset($this->originalValues[$configKey])) {
                        $this->originalValues[$configKey] = $config->getValues();
                    }
                }

                // @todo: Should be removed together with deprecated events
                if ($this->hasListeners(Events::NEW_ENTITY_CONFIG)) {
                    $this->eventDispatcher->dispatch(
                        Events::NEW_ENTITY_CONFIG,
                        new Event\EntityConfigEvent($className, $this)
                    );
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
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param string $mode
     *
     * @return FieldConfigModel
     */
    public function createConfigFieldModel(
        $className,
        $fieldName,
        $fieldType,
        $mode = ConfigModelManager::MODE_DEFAULT
    ) {
        $fieldModel = $this->modelManager->findFieldModel($className, $fieldName);
        if (null === $fieldModel) {
            $fieldModel = $this->modelManager->createFieldModel($className, $fieldName, $fieldType, $mode);
            $metadata   = $this->getFieldMetadata($className, $fieldName);
            foreach ($this->providers as $scope => $provider) {
                $configKey = $scope . '.' . $className . '.' . $fieldName;
                $config    = new Config(
                    new FieldConfigId($scope, $className, $fieldName, $fieldType),
                    $this->getFieldDefaultValues($scope, $className, $fieldName, $fieldType, $metadata)
                );
                $this->mergeConfigValues($config, $configKey);

                // local cache
                $this->cache->saveConfig($config, true);
                $this->cache->saveConfigurable(true, $className, $fieldName, true);
                // for calculate change set
                if (!isset($this->originalValues[$configKey])) {
                    $this->originalValues[$configKey] = $config->getValues();
                }
            }

            // @todo: Should be removed together with deprecated events
            if ($this->hasListeners(Events::NEW_FIELD_CONFIG)) {
                $this->eventDispatcher->dispatch(
                    Events::NEW_FIELD_CONFIG,
                    new Event\FieldConfigEvent($className, $fieldName, $this)
                );
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
     *
     * @TODO: need handling for removed values
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
        foreach ($this->providers as $scope => $provider) {
            $config        = $provider->getConfig($className);
            $defaultValues = $this->getEntityDefaultValues($scope, $className, $metadata);
            $hasChanges    = $this->updateConfigValues($config, $defaultValues, $force);
            if ($hasChanges) {
                $configKey = $scope . '.' . $className;

                $this->persistConfigs[$configKey] = $config;
            }
        }

        // @todo: Should be removed together with deprecated events
        if ($this->hasListeners(Events::UPDATE_ENTITY_CONFIG)) {
            $this->eventDispatcher->dispatch(
                Events::UPDATE_ENTITY_CONFIG,
                new Event\EntityConfigEvent($className, $this)
            );
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
     *
     * @TODO: need handling for removed values
     */
    public function updateConfigFieldModel($className, $fieldName, $force = false)
    {
        // existing values for a custom field must not be overridden
        if ($force && $this->isCustom($className, $fieldName)) {
            $force = false;
        }

        $metadata = $this->getFieldMetadata($className, $fieldName);
        foreach ($this->providers as $scope => $provider) {
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

        // @todo: Should be removed together with deprecated events
        if ($this->hasListeners(Events::UPDATE_FIELD_CONFIG)) {
            $this->eventDispatcher->dispatch(
                Events::UPDATE_FIELD_CONFIG,
                new Event\FieldConfigEvent($className, $fieldName, $this)
            );
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
            // @todo: Should be removed together with deprecated events
            if ($this->hasListeners(Events::RENAME_FIELD_OLD)) {
                $this->eventDispatcher->dispatch(
                    Events::RENAME_FIELD_OLD,
                    new Event\RenameFieldEvent($className, $fieldName, $newFieldName, $this)
                );
            }
            $this->eventDispatcher->dispatch(
                Events::RENAME_FIELD,
                new Event\RenameFieldEvent($className, $fieldName, $newFieldName, $this)
            );
            foreach ($this->providers as $scope => $provider) {
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
     * @param string $mode      Can be the value of one of ConfigModelManager::MODE_* constants
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
     * @param string $mode      Can be the value of one of ConfigModelManager::MODE_* constants
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
     * @param AbstractConfigModel $model
     * @param string              $scope
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
     * @return AbstractConfigModel
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
            ? $configId->getScope() . '.' . $configId->getClassName() . '.' . $configId->getFieldName()
            : $configId->getScope() . '.' . $configId->getClassName();
    }

    /**
     * @param string $scope
     *
     * @return PropertyConfigContainer
     */
    protected function getPropertyConfig($scope)
    {
        if (isset($this->propertyConfigs[$scope])) {
            return $this->propertyConfigs[$scope];
        }

        $propertyConfig = $this->providers[$scope]->getPropertyConfig();

        $this->propertyConfigs[$scope] = $propertyConfig;

        return $propertyConfig;
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

    /**
     * @deprecated since 1.9. Should be removed together with deprecated events
     * @see Oro\Bundle\EntityConfigBundle\Event\Events
     *
     * @param string $eventName
     *
     * @return bool
     */
    private function hasListeners($eventName)
    {
        if (!isset($this->hasListenersCache[$eventName])) {
            $this->hasListenersCache[$eventName] = $this->eventDispatcher->hasListeners($eventName);
        }

        return $this->hasListenersCache[$eventName];
    }
}
