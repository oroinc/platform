<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\EntityManager;

use Metadata\MetadataFactory;

use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityConfigBundle\Exception\LogicException;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

use Oro\Bundle\EntityConfigBundle\Event\NewEntityConfigModelEvent;
use Oro\Bundle\EntityConfigBundle\Event\NewFieldConfigModelEvent;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\UpdateEntityConfigModelEvent;
use Oro\Bundle\EntityConfigBundle\Event\Events;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

/**
 * @SuppressWarnings(PHPMD)
 */
class ConfigManager
{
    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var ConfigCache
     */
    protected $cache;

    /**
     * @var AuditManager
     */
    protected $auditManager;

    /**
     * @var ConfigModelManager
     */
    protected $modelManager;

    /**
     * @var ServiceLink
     */
    protected $providerBag;

    /**
     * key = a string returned by $this->buildConfigKey
     *
     * @var ConfigInterface[]
     */
    protected $localCache;

    /**
     * key = a string returned by $this->buildConfigKey
     *
     * @var ConfigInterface[]
     */
    protected $persistConfigs;

    /**
     * key = a string returned by $this->buildConfigKey
     *
     * @var ConfigInterface[]
     */
    protected $originalConfigs;

    /**
     * key = a string returned by $this->buildConfigKey
     * value = an array
     *
     * @var array
     */
    protected $configChangeSets;

    /**
     * @param MetadataFactory    $metadataFactory
     * @param EventDispatcher    $eventDispatcher
     * @param ServiceLink        $providerBagLink
     * @param ConfigModelManager $modelManager
     * @param AuditManager       $auditManager
     * @param Container          $container
     */
    public function __construct(
        MetadataFactory $metadataFactory,
        EventDispatcher $eventDispatcher,
        ServiceLink $providerBagLink,
        ConfigModelManager $modelManager,
        AuditManager $auditManager,
        Container $container
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->eventDispatcher = $eventDispatcher;

        $this->providerBag      = $providerBagLink;
        $this->localCache       = [];
        $this->persistConfigs   = [];
        $this->originalConfigs  = [];
        $this->configChangeSets = [];

        $this->container        = $container;

        $this->modelManager = $modelManager;
        $this->auditManager = $auditManager;
    }

    /**
     * @param ConfigCache $cache
     */
    public function setCache(ConfigCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->modelManager->getEntityManager();
    }

    /**
     * @return ConfigProviderBag
     */
    public function getProviderBag()
    {
        return $this->providerBag->getService();
    }

    /**
     * @return ConfigProvider[]|ArrayCollection
     */
    public function getProviders()
    {
        return $this->getProviderBag()->getProviders();
    }

    /**
     * @param $scope
     * @return ConfigProvider
     */
    public function getProvider($scope)
    {
        return $this->getProviderBag()->getProvider($scope);
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param $className
     * @return EntityMetadata|null
     */
    public function getEntityMetadata($className)
    {
        return class_exists($className)
            ? $this->metadataFactory->getMetadataForClass($className)
            : null;
    }

    /**
     * @param $className
     * @param $fieldName
     * @return null|FieldMetadata
     */
    public function getFieldMetadata($className, $fieldName)
    {
        $metadata = $this->getEntityMetadata($className);

        return $metadata && isset($metadata->propertyMetadata[$fieldName])
            ? $metadata->propertyMetadata[$fieldName]
            : null;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    public function hasConfig($className, $fieldName = null)
    {
        if (!$this->modelManager->checkDatabase()) {
            return false;
        }

        $result = $this->cache->getConfigurable($className, $fieldName);
        if ($result === false) {
            $result = (bool)$this->modelManager->findModel($className, $fieldName) ? : null;

            $this->cache->setConfigurable($result, $className, $fieldName);
        }

        return $result;
    }

    /**
     * @param string $scope
     * @param string $className
     * @param bool   $withHidden
     * @return array
     */
    public function getIds($scope, $className = null, $withHidden = false)
    {
        if (!$this->modelManager->checkDatabase()) {
            return [];
        }

        if ($withHidden) {
            $entityModels = $this->modelManager->getModels($className);
        } else {
            $entityModels = array_filter(
                $this->modelManager->getModels($className),
                function (AbstractConfigModel $model) {
                    return $model->getMode() != ConfigModelManager::MODE_HIDDEN;
                }
            );
        }

        return array_map(
            function (AbstractConfigModel $model) use ($scope) {
                if ($model instanceof FieldConfigModel) {
                    return new FieldConfigId(
                        $model->getEntity()->getClassName(),
                        $scope,
                        $model->getFieldName(),
                        $model->getType()
                    );
                } else {
                    return new EntityConfigId($model->getClassName(), $scope);
                }
            },
            $entityModels
        );
    }

    /**
     * @param ConfigIdInterface $configId
     * @throws RuntimeException
     * @throws LogicException
     * @return ConfigInterface
     */
    public function getConfig(ConfigIdInterface $configId)
    {
        $configKey = $this->buildConfigKey($configId);
        if (isset($this->localCache[$configKey])) {
            return $this->localCache[$configKey];
        }

        if (!$this->modelManager->checkDatabase()) {
            throw new LogicException(
                'Database is not synced, if you use ConfigManager, when a db schema may be hasn\'t synced.'
                . ' check it by ConfigManager::modelManager::checkDatabase'
            );
        }

        if (!$this->hasConfig($configId->getClassName())) {
            throw new RuntimeException(sprintf('Entity "%s" is not configurable', $configId->getClassName()));
        }

        $config = null !== $this->cache
            ? $this->cache->loadConfigFromCache($configId)
            : null;

        if (!$config) {
            $model = $this->modelManager->getModelByConfigId($configId);

            $config = new Config($this->getConfigIdByModel($model, $configId->getScope()));
            $config->setValues($model->toArray($configId->getScope()));

            if (null !== $this->cache) {
                $this->cache->putConfigInCache($config);
            }
        }

        //local cache
        $this->localCache[$configKey] = $config;

        //for calculate change set
        $this->originalConfigs[$configKey] = clone $config;

        return $config;
    }


    /**
     * @param ConfigIdInterface $configId
     */
    public function clearCache(ConfigIdInterface $configId)
    {
        if ($this->cache) {
            $this->cache->removeConfigFromCache($configId);
        }
    }

    /**
     * Remove All cache
     */
    public function clearCacheAll()
    {
        if ($this->cache) {
            $this->cache->removeAll();
        }
    }

    /**
     * Remove All Configurable cache
     */
    public function clearConfigurableCache()
    {
        if ($this->cache) {
            $this->cache->removeAllConfigurable();
        }
        $this->modelManager->clearCheckDatabase();
    }

    /**
     * @param ConfigInterface $config
     */
    public function persist(ConfigInterface $config)
    {
        $this->persistConfigs[$this->buildConfigKey($config->getId())] = $config;
    }

    /**
     * @param ConfigInterface $config
     * @return ConfigInterface
     */
    public function merge(ConfigInterface $config)
    {
        $configKey = $this->buildConfigKey($config->getId());
        if (isset($this->persistConfigs[$configKey])) {
            $persistValues = $this->persistConfigs[$configKey]->all();
            if (!empty($persistValues)) {
                $config->setValues(array_merge($persistValues, $config->all()));
            }
        }
        $this->persistConfigs[$configKey] = $config;

        return $config;
    }

    public function flush()
    {
        $models = [];

        foreach ($this->persistConfigs as $config) {
            $this->calculateConfigChangeSet($config);

            $this->eventDispatcher->dispatch(Events::PRE_PERSIST_CONFIG, new PersistConfigEvent($config, $this));

            $configKey = $this->buildConfigKey($config->getId());
            if (isset($models[$configKey])) {
                $model = $models[$configKey];
            } else {
                $model = $this->modelManager->getModelByConfigId($config->getId());

                $models[$configKey] = $model;
            }

            if ($model instanceof FieldConfigModel && $model->getType() == 'optionSet' && $config->has('set_options')) {
                $model->setOptions($config->get('set_options'));
            }

            //TODO::refactoring
            $serializableValues = $this->getProvider($config->getId()->getScope())
                ->getPropertyConfig()
                ->getSerializableValues($config->getId());
            $model->fromArray($config->getId()->getScope(), $config->all(), $serializableValues);

            if ($this->cache) {
                $this->cache->removeConfigFromCache($config->getId());
            }
        }

        if ($this->cache) {
            $this->cache->removeAllConfigurable();
        }

        $this->auditManager->log();

        foreach ($models as $model) {
            $this->getEntityManager()->persist($model);
        }

        $this->getEntityManager()->flush();

        $this->persistConfigs   = [];
        $this->configChangeSets = [];
    }


    /**
     * @param ConfigInterface $config
     * @SuppressWarnings(PHPMD)
     */
    public function calculateConfigChangeSet(ConfigInterface $config)
    {
        $originConfigValue = [];
        $configKey         = $this->buildConfigKey($config->getId());
        if (isset($this->originalConfigs[$configKey])) {
            $originConfigValue = $this->originalConfigs[$configKey]->all();
        }

        foreach ($config->all() as $key => $value) {
            if (!isset($originConfigValue[$key])) {
                $originConfigValue[$key] = null;
            }
        }

        $diffNew = array_udiff_assoc(
            $config->all(),
            $originConfigValue,
            function ($a, $b) {
                return ($a == $b) ? 0 : 1;
            }
        );

        $diffOld = array_udiff_assoc(
            $originConfigValue,
            $config->all(),
            function ($a, $b) {
                return ($a == $b) ? 0 : 1;
            }
        );

        $diff = [];
        foreach ($diffNew as $key => $value) {
            $oldValue   = isset($diffOld[$key]) ? $diffOld[$key] : null;
            $diff[$key] = [$oldValue, $value];
        }


        if (!isset($this->configChangeSets[$configKey])) {
            $this->configChangeSets[$configKey] = [];
        }

        if (count($diff)) {
            $changeSet                          = array_merge($this->configChangeSets[$configKey], $diff);
            $this->configChangeSets[$configKey] = $changeSet;
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
     * @return array
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
     * @return bool
     */
    public function hasConfigEntityModel($className)
    {
        return null !== $this->modelManager->findModel($className);
    }

    /**
     * Checks if the configuration model for the given field exist
     *
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    public function hasConfigFieldModel($className, $fieldName)
    {
        return null !== $this->modelManager->findModel($className, $fieldName);
    }

    /**
     * Gets a config model for the given entity
     *
     * @param string $className
     * @return EntityConfigModel|null
     */
    public function getConfigEntityModel($className)
    {
        return $this->hasConfigEntityModel($className)
            ? $this->modelManager->findModel($className)
            : null;
    }

    /**
     * Gets a config model for the given entity field
     *
     * @param string $className
     * @param string $fieldName
     * @return FieldConfigModel|null
     */
    public function getConfigFieldModel($className, $fieldName)
    {
        return $this->hasConfigFieldModel($className, $fieldName)
            ? $this->modelManager->findModel($className, $fieldName)
            : null;
    }

    /**
     * TODO:: check class name for custom entity
     *
     * @param string $className
     * @param string $mode
     * @return EntityConfigModel
     */
    public function createConfigEntityModel($className, $mode = ConfigModelManager::MODE_DEFAULT)
    {
        if (!$entityModel = $this->modelManager->findModel($className)) {
            $entityModel = $this->modelManager->createEntityModel($className, $mode);
            $metadata    = $this->getEntityMetadata($className);

            foreach ($this->getProviders() as $provider) {
                $translatable = $provider->getPropertyConfig()
                    ->getTranslatableValues(PropertyConfigContainer::TYPE_ENTITY);

                $defaultValues = [];
                if ($metadata && isset($metadata->defaultValues[$provider->getScope()])) {
                    $defaultValues = $metadata->defaultValues[$provider->getScope()];
                }

                $entityId = new EntityConfigId($className, $provider->getScope());

                foreach ($translatable as $code) {
                    if (!in_array($code, $defaultValues)) {
                        $defaultValues[$code] = ConfigHelper::getTranslationKey($className, null, $code);
                    }
                }

                $config = $provider->createConfig($entityId, $defaultValues);

                $this->localCache[$this->buildConfigKey($config->getId())] = $config;
            }

            $this->eventDispatcher->dispatch(
                Events::NEW_ENTITY_CONFIG_MODEL,
                new NewEntityConfigModelEvent($entityModel, $this)
            );
        }

        return $entityModel;
    }

    /**
     * @param string $className
     * @param bool $force - if TRUE overwrite existing value from annotation
     *
     * @TODO: need refactoring. Join updateConfigEntityModel and updateConfigFieldModel.
     *        may be need introduce MetadataWithDefaultValuesInterface
     *        need handling for removed values
     *        need refactor getConfig
     *        need to find out more appropriate name for this method
     */
    public function updateConfigEntityModel($className, $force = false)
    {
        $metadata = $this->getEntityMetadata($className);
        foreach ($this->getProviders() as $provider) {
            $scope = $provider->getScope();
            // try to get default values from annotation
            $defaultValues = [];
            if (isset($metadata->defaultValues[$scope])) {
                $defaultValues = $metadata->defaultValues[$scope];
            }
            // combine them with default values from config file
            $defaultValues = array_merge(
                $provider->getPropertyConfig()->getDefaultValues(),
                $defaultValues
            );

            $translatable = $provider->getPropertyConfig()
                ->getTranslatableValues(PropertyConfigContainer::TYPE_ENTITY);

            foreach ($translatable as $code) {
                if (!in_array($code, $defaultValues)) {
                    $defaultValues[$code] = ConfigHelper::getTranslationKey($className, null, $code);
                }
            }

            // set missing values with default ones
            $hasChanges = false;
            $config     = $provider->getConfig($className);
            foreach ($defaultValues as $code => $value) {
                if (!$config->has($code) || !$config->is($code, $value) || $force) {
                    $config->set($code, $value);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                $provider->persist($config);
            }

            if (in_array($scope, UpdateEntityConfigModelEvent::$scopes)) {
                if ($entityModel = $this->getConfigEntityModel($className)) {
                    $this->eventDispatcher->dispatch(
                        Events::UPDATE_ENTITY_CONFIG_MODEL,
                        new UpdateEntityConfigModelEvent($entityModel, $this)
                    );
                }
            }
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param string $mode
     * @return FieldConfigModel
     */
    public function createConfigFieldModel($className, $fieldName, $fieldType, $mode = ConfigModelManager::MODE_DEFAULT)
    {
        if (!$fieldModel = $this->modelManager->findModel($className, $fieldName)) {
            $fieldModel = $this->modelManager->createFieldModel($className, $fieldName, $fieldType, $mode);
            $metadata   = $this->getFieldMetadata($className, $fieldName);

            foreach ($this->getProviders() as $provider) {
                $translatable  = $provider->getPropertyConfig()->getTranslatableValues();
                $defaultValues = [];
                if ($metadata && isset($metadata->defaultValues[$provider->getScope()])) {
                    $defaultValues = $metadata->defaultValues[$provider->getScope()];
                }

                $fieldId = new FieldConfigId($className, $provider->getScope(), $fieldName, $fieldType);

                foreach ($translatable as $code) {
                    if (!in_array($code, $defaultValues)) {
                        $defaultValues[$code] = ConfigHelper::getTranslationKey($className, $fieldName, $code);
                    }
                }

                $config  = $provider->createConfig($fieldId, $defaultValues);

                $this->localCache[$this->buildConfigKey($config->getId())] = $config;
            }

            $this->eventDispatcher->dispatch(
                Events::NEW_FIELD_CONFIG_MODEL,
                new NewFieldConfigModelEvent($fieldModel, $this)
            );
        }

        return $fieldModel;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param bool $force - if TRUE overwrite existing value from annotation
     *
     * @TODO: need refactoring. Join updateConfigEntityModel and updateConfigFieldModel.
     *        may be need introduce MetadataWithDefaultValuesInterface
     *        need handling for removed values
     *        need refactor getConfig
     *        need to find out more appropriate name for this method
     */
    public function updateConfigFieldModel($className, $fieldName, $force = false)
    {
        $metadata = $this->getFieldMetadata($className, $fieldName);
        foreach ($this->getProviders() as $provider) {
            $scope = $provider->getScope();
            // try to get default values from annotation
            $defaultValues = [];
            if (isset($metadata->defaultValues[$scope])) {
                $defaultValues = $metadata->defaultValues[$scope];
            }
            // combine them with default values from config file
            $defaultValues = array_merge(
                $provider->getPropertyConfig()->getDefaultValues(),
                $defaultValues
            );

            // set missing values with default ones
            $hasChanges = false;
            $config     = $provider->getConfig($className, $fieldName);
            foreach ($defaultValues as $code => $value) {
                if (!$config->has($code) || !$config->is($code, $value) || $force) {
                    $config->set($code, $value);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                $provider->persist($config);
            }
        }
    }

    private function getConfigIdByModel(AbstractConfigModel $model, $scope)
    {
        if ($model instanceof FieldConfigModel) {
            return new FieldConfigId(
                $model->getEntity()->getClassName(),
                $scope,
                $model->getFieldName(),
                $model->getType()
            );
        } else {
            return new EntityConfigId(
                $model->getClassName(),
                $scope
            );
        }
    }

    /**
     * Returns a string unique identifies each config item
     *
     * @param ConfigIdInterface $configId
     * @return string
     */
    protected function buildConfigKey(ConfigIdInterface $configId)
    {
        return $configId instanceof FieldConfigId
            ? sprintf('%s_%s_%s', $configId->getScope(), $configId->getClassName(), $configId->getFieldName())
            : sprintf('%s_%s', $configId->getScope(), $configId->getClassName());
    }
}
