<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;

/**
 * Warms up entity configuration cache.
 * IMPORTANT: A performance of this class is very crucial. Double check a performance during a refactoring.
 */
class ConfigCacheWarmer
{
    /**
     * Determines whether it is needed to warm up a cache
     * for both configurable and non configurable entities and fields.
     */
    public const MODE_ALL = 0;

    /**
     * Determines whether it is needed to warm up a cache for configurable entities and fields only.
     * A cache for non configurable entities and fields will not be warmed up.
     */
    public const MODE_CONFIGURABLE_ONLY = 1;

    /**
     * Determines whether it is needed to warm up a cache for configurable entities only.
     * A cache for configurable fields and non configurable entities and fields will not be warmed up.
     */
    public const MODE_CONFIGURABLE_ENTITY_ONLY = 2;

    /** @var ConfigManager */
    private $configManager;

    /** @var ConfigCache */
    private $cache;

    /** @var LockObject */
    private $configModelLockObject;

    /** @var EntityManagerBag */
    private $entityManagerBag;

    /** @var VirtualFieldProviderInterface */
    private $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface */
    private $virtualRelationProvider;

    /** @var array|null */
    private $emptyData;

    /** @var array|null */
    private $configurableEntitiesMap;

    public function __construct(
        ConfigManager $configManager,
        ConfigCache $cache,
        LockObject $configModelLockObject,
        EntityManagerBag $entityManagerBag,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider
    ) {
        $this->configManager = $configManager;
        $this->cache = $cache;
        $this->configModelLockObject = $configModelLockObject;
        $this->entityManagerBag = $entityManagerBag;
        $this->virtualFieldProvider = $virtualFieldProvider;
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

        $this->cache->beginBatch();
        try {
            $this->loadConfigurable($mode === self::MODE_CONFIGURABLE_ENTITY_ONLY);
            if ($mode === self::MODE_ALL) {
                // disallow to load new models
                $this->configModelLockObject->lock();
                try {
                    $this->loadNonConfigurable();
                    $this->loadVirtualFields();
                } finally {
                    $this->configModelLockObject->unlock();
                    $this->configurableEntitiesMap = null;
                }
            }
            $this->cache->saveBatch();
        } catch (\Throwable $e) {
            $this->cache->cancelBatch();
            throw $e;
        }
    }

    /**
     * @return array
     */
    private function getEmptyData()
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
    private function loadConfigurable($skipFields)
    {
        if (null === $this->configurableEntitiesMap) {
            $this->configurableEntitiesMap = $this->loadConfigurableEntities();
        }
        if (!$skipFields) {
            $fieldFlags = $this->loadConfigurableFields($this->configurableEntitiesMap);
            foreach ($this->configurableEntitiesMap as $entityId => $className) {
                $this->cache->saveConfigurableValues(
                    $className,
                    true,
                    $fieldFlags[$entityId] ?? []
                );
            }
        }
    }

    /**
     * @return array [entity_config_id => class_name, ...]
     */
    private function loadConfigurableEntities()
    {
        $connection = $this->configManager->getEntityManager()->getConnection();
        $entityRows = $connection
            ->executeQuery('SELECT id, class_name, mode, data FROM oro_entity_config');

        $classMap = [];
        $configValues = [];
        $entities = [];
        foreach ($entityRows as $row) {
            $entityId = (int)$row['id'];
            $className = $row['class_name'];
            $isHidden = $row['mode'] === ConfigModel::MODE_HIDDEN;

            $configs = array_merge($this->getEmptyData(), $connection->convertToPHPValue($row['data'], 'array'));
            foreach ($configs as $scope => $config) {
                $configValues[$scope][$className] = $config;
            }

            $classMap[$entityId] = $className;
            $entities[$className] = [
                'i' => $entityId,
                'h' => $isHidden
            ];
        }
        foreach ($configValues as $scope => $values) {
            $this->cache->saveEntityConfigValues($values, $scope);
        }
        $this->cache->saveEntities($entities);

        return $classMap;
    }

    /**
     * @param array $classMap [class_name => entity_config_id, ...]
     *
     * @return array [entity_config_id => [field_name => is_configurable, ...], ...]
     */
    private function loadConfigurableFields(array $classMap)
    {
        $connection = $this->configManager->getEntityManager()->getConnection();
        $fieldRows = $connection
            ->executeQuery('SELECT id, entity_id, field_name, type, mode, data FROM oro_entity_config_field');

        $configurable = [];
        $configValues = [];
        $fields = [];
        foreach ($fieldRows as $row) {
            $entityId = (int)$row['entity_id'];
            if (!isset($classMap[$entityId])) {
                continue;
            }

            $fieldId = (int)$row['id'];
            $className = $classMap[$entityId];
            $fieldName = $row['field_name'];
            $fieldType = $row['type'];
            $isHidden = $row['mode'] === ConfigModel::MODE_HIDDEN;

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

            $configs = array_merge($this->getEmptyData(), $connection->convertToPHPValue($row['data'], 'array'));
            foreach ($configs as $scope => $config) {
                $configValues[$className][$scope][$fieldName] = [$config, $fieldType];
            }

            $configurable[$entityId][$fieldName] = true;
            $fields[$className][$fieldName] = [
                'i' => $fieldId,
                'h' => $isHidden,
                't' => $fieldType
            ];
        }

        foreach ($configValues as $className => $classValues) {
            foreach ($classValues as $scope => $values) {
                $this->cache->saveFieldConfigValues($values, $scope, $className);
            }
        }
        foreach ($fields as $className => $values) {
            $this->cache->saveFields($className, $values);
        }

        return $configurable;
    }

    private function loadNonConfigurable()
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

    private function loadVirtualFields()
    {
        $entities = $this->cache->getEntities();
        foreach ($entities as $className => $entityData) {
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
