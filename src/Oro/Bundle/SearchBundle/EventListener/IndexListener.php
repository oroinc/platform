<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Event\BeforeEntityAddToIndexEvent;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Doctrine event listener which collects changes and updates search index
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class IndexListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var IndexerInterface
     */
    protected $searchIndexer;

    /**
     * @var SearchMappingProvider
     */
    protected $mappingProvider;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $savedEntities = [];

    /**
     * @var array
     */
    protected $deletedEntities = [];

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /** @var array */
    protected $entitiesIndexedFieldsCache = [];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        IndexerInterface $searchIndexer,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->doctrineHelper   = $doctrineHelper;
        $this->searchIndexer    = $searchIndexer;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function setMappingProvider(SearchMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $entityManager = $args->getEntityManager();
        $this->scheduleSavedEntities($entityManager);
        $this->scheduleDeletedEntities($entityManager);
    }

    private function scheduleSavedEntities(EntityManager $entityManager): void
    {
        $uow = $entityManager->getUnitOfWork();

        $this->savedEntities = array_replace(
            $this->savedEntities,
            $this->getEntitiesToReindex($uow),
            $this->getEntitiesWithUpdatedIndexedFields($uow),
            $this->getAssociatedEntitiesToReindex($entityManager, $uow->getScheduledEntityInsertions()),
            $this->getAssociatedEntitiesToReindex($entityManager, $uow->getScheduledEntityUpdates()),
            $this->getAssociatedEntitiesToReindex($entityManager, $uow->getScheduledEntityDeletions()),
            $this->getEntitiesFromUpdatedCollections($uow)
        );
    }

    /**
     * Deleted entities should be processed as references because on postFlush they are already deleted.
     */
    private function scheduleDeletedEntities(EntityManager $entityManager): void
    {
        $uow = $entityManager->getUnitOfWork();
        foreach ($uow->getScheduledEntityDeletions() as $objId => $entity) {
            if (empty($this->deletedEntities[$objId]) && $this->isSupported($entity)) {
                $this->deletedEntities[$objId] = $entityManager->getReference(
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
                    $entities[spl_object_id($owner)] = $owner;
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

        foreach ($uow->getScheduledEntityUpdates() as $objId => $entity) {
            $className = ClassUtils::getClass($entity);
            $changedIndexedFields = $this->getIntersectChangedIndexedFields(
                $className,
                array_keys($uow->getEntityChangeSet($entity))
            );
            if ($changedIndexedFields) {
                $entitiesToReindex[$objId] = $entity;
            }
        }

        return $entitiesToReindex;
    }

    protected function getEntitiesToReindex(UnitOfWork $uow): array
    {
        $entitiesToReindex = [];

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($this->isInsertSupported($entity)) {
                $entitiesToReindex[spl_object_id($entity)] = $entity;
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
                $associationValue = $this->getAssociationValue($entity, $association);
                if (null !== $associationValue) {
                    if ($associationValue instanceof Collection) {
                        foreach ($associationValue->toArray() as $value) {
                            $entitiesToReindex[spl_object_id($value)] = $value;
                        }
                    } else {
                        $entitiesToReindex[spl_object_id($associationValue)] = $associationValue;
                    }
                }
            }
        }

        return $entitiesToReindex;
    }

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
     */
    public function onClear(OnClearEventArgs $args)
    {
        if (!($this->enabled && $this->hasEntitiesToIndex())) {
            return;
        }

        $this->savedEntities = $this->deletedEntities = [];
    }

    /**
     * @param object $entity
     * @param array $association
     *
     * @return mixed
     */
    protected function getAssociationValue($entity, array $association)
    {
        $relationField = $this->getRelationField($association);
        if (null === $relationField) {
            return null;
        }

        $targetClass = $association['targetEntity'];
        $changedIndexedFields = $this->getIntersectChangedIndexedFields($targetClass, [$relationField]);
        if (!$changedIndexedFields) {
            return null;
        }

        return $this->propertyAccessor->getValue($entity, $association['fieldName']);
    }

    /**
     * Synchronise all changed entities with search index
     */
    protected function indexEntities()
    {
        foreach ($this->deletedEntities as $objId => $entity) {
            if (array_key_exists($objId, $this->savedEntities)) {
                unset($this->savedEntities[$objId]);
            }
        }

        if ($this->savedEntities) {
            $this->searchIndexer->save($this->savedEntities);

            $this->savedEntities = [];
        }

        if ($this->deletedEntities) {
            $this->searchIndexer->delete($this->deletedEntities);

            $this->deletedEntities = [];
        }
    }

    /**
     * @param object $entity
     * @return bool
     */
    private function isInsertSupported($entity)
    {
        if (!$this->isSupported($entity)) {
            return false;
        }

        $event = new BeforeEntityAddToIndexEvent($entity);
        $this->dispatcher->dispatch($event, BeforeEntityAddToIndexEvent::EVENT_NAME);

        return null !== $event->getEntity();
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
            if ($fieldConfig['name'] === null) {
                continue;
            }

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

    /**
     * @param array $association
     *
     * @return string
     */
    private function getRelationField(array $association)
    {
        if ($association['type'] === ClassMetadataInfo::MANY_TO_MANY) {
            if ($association['mappedBy'] !== null) {
                return $association['mappedBy'];
            }

            return $association['inversedBy'];
        }

        if ($association['type'] === ClassMetadataInfo::MANY_TO_ONE) {
            return $association['inversedBy'];
        }

        return null;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }
}
