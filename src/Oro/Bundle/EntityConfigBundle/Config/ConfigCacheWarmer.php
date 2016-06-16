<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * IMPORTANT: A performance of this class is very crucial. Double check a performance during a refactoring.
 */
class ConfigCacheWarmer
{
    /**
     * Determines whether it is needed to warm up a cache
     * for both configurable and non configurable entities and fields.
     */
    const MODE_ALL = 0;

    /**
     * Determines whether it is needed to warm up a cache for configurable entities and fields only.
     * A cache for non configurable entities and fields will not be warmed up.
     */
    const MODE_CONFIGURABLE_ONLY = 1;

    /**
     * Determines whether it is needed to warm up a cache for configurable entities only.
     * A cache for configurable fields and non configurable entities and fields will not be warmed up.
     */
    const MODE_CONFIGURABLE_ENTITY_ONLY = 2;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigCache */
    protected $cache;

    /** @var LockObject */
    protected $configModelLockObject;

    /** @var EntityManagerBag */
    protected $entityManagerBag;

    /** @var VirtualFieldProviderInterface */
    protected $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface */
    protected $virtualRelationProvider;

    /** @var array|null */
    private $emptyData;

    /**
     * @param ConfigManager                    $configManager
     * @param ConfigCache                      $cache
     * @param LockObject                       $configModelLockObject
     * @param EntityManagerBag                 $entityManagerBag
     * @param VirtualFieldProviderInterface    $virtualFieldProvider
     * @param VirtualRelationProviderInterface $virtualRelationProvider
     */
    public function __construct(
        ConfigManager $configManager,
        ConfigCache $cache,
        LockObject $configModelLockObject,
        EntityManagerBag $entityManagerBag,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider
    ) {
        $this->configManager           = $configManager;
        $this->cache                   = $cache;
        $this->configModelLockObject   = $configModelLockObject;
        $this->entityManagerBag        = $entityManagerBag;
        $this->virtualFieldProvider    = $virtualFieldProvider;
        $this->virtualRelationProvider = $virtualRelationProvider;
    }

    /**
     * Warms up the configuration data cache.
     *
     * @param int $mode One of MODE_* constant
     */
    public function warmUpCache($mode = self::MODE_ALL)
    {
        if (!$this->configManager->isDatabaseReadyToWork()) {
            return;
        }

        $this->loadConfigurable($mode === self::MODE_CONFIGURABLE_ENTITY_ONLY);
        if ($mode === self::MODE_ALL) {
            // disallow to load new models
            $this->configModelLockObject->lock();
            try {
                $this->loadNonConfigurable();
                $this->loadVirtualFields();
                $this->configModelLockObject->unlock();
            } catch (\Exception $e) {
                $this->configModelLockObject->unlock();
                throw $e;
            }
        }
    }

    /**
     * @return array
     */
    protected function getEmptyData()
    {
        if (null === $this->emptyData) {
            $this->emptyData = [];

            $providers = $this->configManager->getProviders();
            foreach ($providers as $scope => $provider) {
                $this->emptyData[$scope] = [];
            }
        }

        return $this->emptyData;
    }

    /**
     * @param bool $skipFields
     */
    protected function loadConfigurable($skipFields)
    {
        $classMap = $this->loadConfigurableEntities();
        if ($skipFields) {
            foreach ($classMap as $className) {
                $this->cache->saveConfigurable(true, $className);
            }
        } else {
            $fieldFlags = $this->loadConfigurableFields($classMap);
            foreach ($classMap as $entityId => $className) {
                $this->cache->saveConfigurableValues(
                    $className,
                    true,
                    isset($fieldFlags[$entityId]) ? $fieldFlags[$entityId] : []
                );
            }
        }
    }

    /**
     * @return array [entity_config_id => class_name, ...]
     */
    protected function loadConfigurableEntities()
    {
        $connection = $this->configManager->getEntityManager()->getConnection();
        $entityRows = $connection
            ->executeQuery('SELECT id, class_name, mode, data FROM oro_entity_config');

        $classMap = [];
        $entities = [];
        foreach ($entityRows as $row) {
            $entityId  = (int)$row['id'];
            $className = $row['class_name'];
            $isHidden  = $row['mode'] === ConfigModel::MODE_HIDDEN;
            $data      = array_merge($this->getEmptyData(), $connection->convertToPHPValue($row['data'], 'array'));

            $classMap[$entityId]  = $className;
            $entities[$className] = [
                'i' => $entityId,
                'h' => $isHidden
            ];

            $this->cache->saveEntityConfigValues($data, $className);
        }
        $this->cache->saveEntities($entities);

        return $classMap;
    }

    /**
     * @param array $classMap [class_name => entity_config_id, ...]
     *
     * @return array [entity_config_id => [field_name => is_configurable, ...], ...]
     */
    protected function loadConfigurableFields(array $classMap)
    {
        $connection = $this->configManager->getEntityManager()->getConnection();
        $fieldRows  = $connection
            ->executeQuery('SELECT id, entity_id, field_name, type, mode, data FROM oro_entity_config_field');

        $configurable = [];
        $fields       = [];
        foreach ($fieldRows as $row) {
            $fieldId  = (int)$row['id'];
            $entityId = (int)$row['entity_id'];
            if (!isset($classMap[$entityId])) {
                continue;
            }
            $className = $classMap[$entityId];
            $fieldName = $row['field_name'];
            $fieldType = $row['type'];
            $isHidden  = $row['mode'] === ConfigModel::MODE_HIDDEN;
            $data      = array_merge($this->getEmptyData(), $connection->convertToPHPValue($row['data'], 'array'));

            $configurable[$entityId][$fieldName] = true;
            $fields[$className][$fieldName]      = [
                'i' => $fieldId,
                'h' => $isHidden,
                't' => $fieldType
            ];

            $this->cache->saveFieldConfigValues($data, $className, $fieldName, $fieldType);
        }
        foreach ($fields as $className => $values) {
            $this->cache->saveFields($className, $values);
        }

        return $configurable;
    }

    protected function loadNonConfigurable()
    {
        $cached = $this->cache->getEntities();

        $entityManagers = $this->entityManagerBag->getEntityManagers();
        foreach ($entityManagers as $em) {
            /** @var ClassMetadata[] $allMetadata */
            $allMetadata = $em->getMetadataFactory()->getAllMetadata();
            foreach ($allMetadata as $metadata) {
                if ($metadata->isMappedSuperclass) {
                    continue;
                }

                $className = $metadata->getName();
                if (!isset($cached[$className])) {
                    $fieldFlags = [];

                    $fieldNames = $metadata->getFieldNames();
                    foreach ($fieldNames as $fieldName) {
                        $fieldFlags[$fieldName] = false;
                    }
                    $fieldNames = $metadata->getAssociationNames();
                    foreach ($fieldNames as $fieldName) {
                        $fieldFlags[$fieldName] = false;
                    }

                    $this->cache->saveConfigurableValues($className, false, $fieldFlags);
                }
            }
        }
    }

    protected function loadVirtualFields()
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');

        $entities = $this->cache->getEntities();
        foreach ($entities as $className => $entityData) {
            if ($extendConfigProvider->getConfig($className)->is('state', ExtendScope::STATE_NEW)) {
                continue;
            }

            $virtualFields = $this->virtualFieldProvider->getVirtualFields($className);
            if (!empty($virtualFields)) {
                foreach ($virtualFields as $fieldName) {
                    if (null === $this->cache->getConfigurable($className, $fieldName)) {
                        $this->cache->saveConfigurable(false, $className, $fieldName);
                    }
                }
            }
            $virtualRelations = $this->virtualRelationProvider->getVirtualRelations($className);
            if (!empty($virtualRelations)) {
                foreach ($virtualRelations as $fieldName => $config) {
                    if (null === $this->cache->getConfigurable($className, $fieldName)) {
                        $this->cache->saveConfigurable(false, $className, $fieldName);
                    }
                }
            }
        }
    }
}
