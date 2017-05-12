<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class IndexListener implements OptionalListenerInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EngineInterface
     */
    protected $searchEngine;

    /**
     * @var bool
     */
    protected $realTimeUpdate = true;

    /**
     * @var array
     * @deprecated since 1.8 Please use mappingProvider for mapping config
     */
    protected $entitiesConfig = [];

    /**
     * @var SearchMappingProvider
     */
    protected $mappingProvider;

    /**
     * @var array
     */
    protected $savedEntities = [];

    /**
     * @var array
     */
    protected $deletedEntities = [];

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var array
     */
    protected $entitiesIndexedFieldsCache = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EngineInterface $searchEngine
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EngineInterface $searchEngine,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->doctrineHelper   = $doctrineHelper;
        $this->searchEngine    = $searchEngine;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param $realTime bool
     */
    public function setRealTimeUpdate($realTime)
    {
        $this->realTimeUpdate = $realTime;
    }

    /**
     * @param array $entities
     * @deprecated since 1.8 Please use mappingProvider for mapping config
     */
    public function setEntitiesConfig(array $entities)
    {
        $this->entitiesConfig = $entities;
    }

    /**
     * @param SearchMappingProvider $mappingProvider
     */
    public function setMappingProvider(SearchMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        // schedule saved entities
        // inserted and updated entities should be processed as is
        $inserts = $unitOfWork->getScheduledEntityInsertions();
        $updates = $unitOfWork->getScheduledEntityUpdates();
        $deletedEntities = $unitOfWork->getScheduledEntityDeletions();
        $this->savedEntities = array_merge(
            $this->savedEntities,
            $this->getEntitiesWithUpdatedIndexedFields($unitOfWork),
            $this->getAssociatedEntitiesToReindex($entityManager, $inserts),
            $this->getAssociatedEntitiesToReindex($entityManager, $updates),
            $this->getEntitiesFromUpdatedCollections($unitOfWork)
        );

        foreach ($inserts as $object) {
            if ($this->isSupported($object)) {
                $this->savedEntities[spl_object_hash($object)] = $object;
            }
        }
        // schedule deleted entities
        // deleted entities should be processed as references because on postFlush they are already deleted
        foreach ($deletedEntities as $hash => $entity) {
            if (empty($this->deletedEntities[$hash]) && $this->isSupported($entity)) {
                $this->deletedEntities[$hash] = $entityManager->getReference(
                    $this->doctrineHelper->getEntityClass($entity),
                    $this->doctrineHelper->getSingleEntityIdentifier($entity)
                );
            }
        }
    }

    /**
     * @param UnitOfWork $uow
     *
     * @return array
     */
    protected function getEntitiesFromUpdatedCollections(UnitOfWork $uow)
    {
        $collectionUpdates = $uow->getScheduledCollectionUpdates();
        // collect owners of all changed collections if owner class has mapping to collection field
        return array_reduce(
            $collectionUpdates,
            function (array $entities, PersistentCollection $collection) {
                $owner        = $collection->getOwner();
                $className     = ClassUtils::getClass($owner);
                $changedFields = $this->getIntersectChangedIndexedFields(
                    $className,
                    [$collection->getMapping()['fieldName']]
                );
                if ($changedFields) {
                    $entities[spl_object_hash($owner)] = $owner;
                }
                return $entities;
            },
            []
        );
    }

    /**
     * @param UnitOfWork $uow
     *
     * @return object[]
     */
    protected function getEntitiesWithUpdatedIndexedFields(UnitOfWork $uow)
    {
        $entitiesToReindex = [];

        foreach ($uow->getScheduledEntityUpdates() as $hash => $entity) {
            $className = ClassUtils::getClass($entity);
            $changedIndexedFields = $this->getIntersectChangedIndexedFields(
                $className,
                array_keys($uow->getEntityChangeSet($entity))
            );
            if ($changedIndexedFields) {
                $entitiesToReindex[$hash] = $entity;
            }
        }

        return $entitiesToReindex;
    }

    /**
     * @param EntityManager $entityManager
     * @param array $entities
     *
     * @return array
     */
    protected function getAssociatedEntitiesToReindex(EntityManager $entityManager, $entities)
    {
        $entitiesToReindex = [];

        foreach ($entities as $entity) {
            $className = ClassUtils::getClass($entity);
            $meta = $entityManager->getClassMetadata($className);

            foreach ($meta->getAssociationMappings() as $association) {
                if (!empty($association['inversedBy']) && $association['type'] === ClassMetadataInfo::MANY_TO_ONE) {
                    $targetClass = $association['targetEntity'];
                    $changedIndexedFields = $this->getIntersectChangedIndexedFields(
                        $targetClass,
                        [$association['inversedBy']]
                    );
                    if (!$changedIndexedFields) {
                        continue;
                    }

                    $associationValue = $this->propertyAccessor->getValue($entity, $association['fieldName']);
                    $entitiesToReindex[spl_object_hash($associationValue)] = $associationValue;
                }
            }
        }

        return $entitiesToReindex;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->hasEntitiesToIndex()) {
            $this->indexEntities();
        }
    }

    /**
     * Clear object storage when error was occurred during UOW#Commit
     *
     * @param OnClearEventArgs $args
     */
    public function onClear(OnClearEventArgs $args)
    {
        if (!($this->enabled && $this->hasEntitiesToIndex())) {
            return;
        }

        $this->savedEntities = $this->deletedEntities = [];
    }

    /**
     * Synchronise all changed entities with search index
     */
    protected function indexEntities()
    {
        if ($this->savedEntities) {
            $this->searchEngine->save($this->savedEntities);

            $this->savedEntities = [];
        }

        if ($this->deletedEntities) {
            $this->searchEngine->delete($this->deletedEntities);

            $this->deletedEntities = [];
        }
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isSupported($entity)
    {
        return !empty($this->getEntityIndexedFields(ClassUtils::getClass($entity)));
    }

    /**
     * @return bool
     */
    protected function hasEntitiesToIndex()
    {
        return !empty($this->savedEntities) || !empty($this->deletedEntities);
    }

    /**
     * @param string $className
     *
     * @return array
     */
    protected function getEntityIndexedFields($className)
    {
        if (isset($this->entitiesIndexedFieldsCache[$className])) {
            return $this->entitiesIndexedFieldsCache[$className];
        }
        if (!$this->mappingProvider->hasFieldsMapping($className)) {
            $this->entitiesIndexedFieldsCache[$className] = [];

            return [];
        }

        $entityConfig = $this->mappingProvider->getEntityConfig($className);

        foreach ($entityConfig['fields'] as $fieldConfig) {
            $this->entitiesIndexedFieldsCache[$className][$fieldConfig['name']] = $fieldConfig['name'];
        }

        return $this->entitiesIndexedFieldsCache[$className];
    }

    /**
     * Returns intersection of indexed fields and changed fields for the given class
     *
     * @param string $className
     * @param array  $changedFields
     *
     * @return array
     */
    protected function getIntersectChangedIndexedFields($className, array $changedFields)
    {
        return array_intersect($this->getEntityIndexedFields($className), $changedFields);
    }
}
