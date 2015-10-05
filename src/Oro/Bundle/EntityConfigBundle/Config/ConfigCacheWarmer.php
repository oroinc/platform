<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;

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
     * @param EntityManagerBag                 $entityManagerBag
     * @param VirtualFieldProviderInterface    $virtualFieldProvider
     * @param VirtualRelationProviderInterface $virtualRelationProvider
     */
    public function __construct(
        ConfigManager $configManager,
        ConfigCache $cache,
        EntityManagerBag $entityManagerBag,
        VirtualFieldProviderInterface $virtualFieldProvider,
        VirtualRelationProviderInterface $virtualRelationProvider
    ) {
        $this->configManager           = $configManager;
        $this->cache                   = $cache;
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
        $classMap = $this->loadConfigurableEntities();
        if ($mode === self::MODE_ALL || $mode === self::MODE_CONFIGURABLE_ONLY) {
            $this->loadConfigurableFields($classMap);
            if ($mode === self::MODE_ALL) {
                $this->loadNonConfigurable();
                $this->loadVirtualFields();
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
            foreach ($this->configManager->getProviders() as $scope => $provider) {
                $this->emptyData[$scope] = [];
            }
        }

        return $this->emptyData;
    }

    /**
     * @return array [class_name => entity_config_id, ...]
     */
    protected function loadConfigurableEntities()
    {
        $entityRows = $this->configManager->getEntityManager()
            ->getRepository('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->createQueryBuilder('e')
            ->select('e.id, e.className, e.mode, e.data')
            ->getQuery()
            ->getArrayResult();

        $classMap = [];
        $entities = [];
        foreach ($entityRows as $row) {
            $entityId  = $row['id'];
            $className = $row['className'];
            $isHidden  = $row['mode'] === ConfigModel::MODE_HIDDEN;
            $data      = array_merge($this->getEmptyData(), $row['data']);

            $classMap[$entityId]  = $className;
            $entities[$className] = $isHidden;

            $this->cache->saveConfigurable(true, $className);
            $this->cache->saveEntityConfigValues($data, $className);
        }
        $this->cache->saveEntities($entities);

        return $classMap;
    }

    /**
     * @param array $classMap [class_name => entity_config_id, ...]
     */
    protected function loadConfigurableFields(array $classMap)
    {
        $fieldRows = $this->configManager->getEntityManager()
            ->getRepository('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->createQueryBuilder('f')
            ->select('IDENTITY(f.entity) AS entityId, f.fieldName, f.type, f.mode, f.data')
            ->getQuery()
            ->getArrayResult();

        $fields = [];
        foreach ($fieldRows as $row) {
            $entityId = $row['entityId'];
            if (!isset($classMap[$entityId])) {
                continue;
            }
            $className = $classMap[$entityId];
            $fieldName = $row['fieldName'];
            $fieldType = $row['type'];
            $isHidden  = $row['mode'] === ConfigModel::MODE_HIDDEN;
            $data      = array_merge($this->getEmptyData(), $row['data']);

            $fields[$className][$fieldName] = ['t' => $fieldType, 'h' => $isHidden];

            $this->cache->saveConfigurable(true, $className, $fieldName);
            $this->cache->saveFieldConfigValues($data, $className, $fieldName, $fieldType);
        }
        foreach ($fields as $className => $values) {
            $this->cache->saveFields($className, $values);
        }
    }

    protected function loadNonConfigurable()
    {
        $cached = $this->cache->getEntities();
        foreach ($this->entityManagerBag->getEntityManagers() as $em) {
            /** @var ClassMetadata $metadata */
            foreach ($em->getMetadataFactory()->getAllMetadata() as $metadata) {
                if ($metadata->isMappedSuperclass) {
                    continue;
                }

                $className = $metadata->getName();
                if (!isset($cached[$className]) && null === $this->cache->getConfigurable($className)) {
                    $this->cache->saveConfigurable(false, $className);
                    foreach ($metadata->getFieldNames() as $fieldName) {
                        $this->cache->saveConfigurable(false, $className, $fieldName);
                    }
                    foreach ($metadata->getAssociationNames() as $fieldName) {
                        $this->cache->saveConfigurable(false, $className, $fieldName);
                    }
                }
            }
        }
    }

    protected function loadVirtualFields()
    {
        foreach ($this->cache->getEntities() as $className => $isHidden) {
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
