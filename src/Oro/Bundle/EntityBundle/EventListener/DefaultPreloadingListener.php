<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\EntityBundle\Event\PreloadEntityEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Default preloading listener for entities.
 */
class DefaultPreloadingListener
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var array */
    private $entityIdField = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(DoctrineHelper $doctrineHelper, PropertyAccessorInterface $propertyAccessor)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param PreloadEntityEvent $preloadEntityEvent
     */
    public function onPreload(PreloadEntityEvent $preloadEntityEvent): void
    {
        $entities = $preloadEntityEvent->getEntities();
        $firstEntity = current($entities);
        if (!$firstEntity) {
            return;
        }

        // Base entity class.
        $entityClass = $this->doctrineHelper->getEntityClass($firstEntity);

        // Fields to preload from base entities.
        $fieldsToPreload = $preloadEntityEvent->getFieldsToPreload();

        /** @var ClassMetadata $entityMetadata */
        $entityMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $entityIdField = $this->getEntityIdField($entityClass);

        // Contains not initialized proxied base entities.
        $entitiesToLoad = [];
        // Contains base entities whose collections from TO_MANY associations should be preloaded.
        $entitiesToLoadCollections = [];
        // Contains not initialized proxied entities from TO_ONE associations of base entities.
        $targetEntitiesByIds = [];

        // Goes through base entities:
        // 1) picks those to load from proxy and puts to $entitiesToLoad
        // 2) picks those which have collections to preload and puts to $entitiesToLoadCollections
        // 3) picks those which are already initialized, collects entities from their TO_ONE associations and puts
        // to $targetEntitiesByIds
        foreach ($entities as $entity) {
            $entityId = $this->propertyAccessor->getValue($entity, $entityIdField);

            if ($this->isProxyAndNotInitialized($entity)) {
                $entitiesToLoad[$entityId] = $entity;
                $entitiesToLoadCollections[$entityId] = $entity;
            } else {
                foreach ($fieldsToPreload as $targetField) {
                    $targetFieldItem = $this->propertyAccessor->getValue($entity, $targetField);
                    if ($this->isProxyAndNotInitialized($targetFieldItem)
                        && !$preloadEntityEvent->hasSubFields($targetField)) {
                        $targetEntityClass = $entityMetadata->getAssociationTargetClass($targetField);
                        $targetEntityIdField = $this->getEntityIdField($targetEntityClass);
                        $targetEntityId = $this->propertyAccessor->getValue($targetFieldItem, $targetEntityIdField);
                        $targetEntitiesByIds[$targetEntityClass][$targetEntityId] = $targetFieldItem;
                        continue;
                    }

                    if ($this->isCollectionAndNotInitialized($targetFieldItem)) {
                        $entitiesToLoadCollections[$entityId] = $entity;
                    }
                }
            }
        }

        $this->loadMain($entityClass, $entitiesToLoad, $fieldsToPreload);
        $this->loadCollections($entityClass, $entitiesToLoadCollections, $fieldsToPreload);
        $this->loadByIds($targetEntitiesByIds);
    }

    /**
     * @param string $entityClass
     * @return string
     */
    private function getEntityIdField(string $entityClass): string
    {
        if (!isset($this->entityIdField[$entityClass])) {
            $this->entityIdField[$entityClass] = $this->doctrineHelper
                ->getSingleEntityIdentifierFieldName($entityClass);
        }

        return $this->entityIdField[$entityClass];
    }

    /**
     * Loads entities along with TO_ONE fields if any.
     *
     * @param string $entityClass
     * @param array $entities
     * @param array $fieldsToPreload
     */
    private function loadMain(string $entityClass, array $entities, array $fieldsToPreload): void
    {
        if (!$entities) {
            return;
        }

        /** @var EntityRepository $entityRepository */
        $entityRepository = $this->doctrineHelper->getEntityRepositoryForClass($entityClass);
        /** @var ClassMetadata $entityMetadata */
        $entityMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        $qb = $entityRepository->createQueryBuilder('entity');
        $qb
            ->where($qb->expr()->in('entity', ':entities'))
            ->setParameter('entities', array_keys($entities));

        foreach ($fieldsToPreload as $targetField) {
            $assocType = $entityMetadata->getAssociationMapping($targetField)['type'];
            if ($assocType & ClassMetadata::TO_ONE) {
                $qb
                    ->addSelect('entity_' . $targetField)
                    ->leftJoin('entity.' . $targetField, 'entity_' . $targetField);
            }
        }

        $qb->getQuery()->execute();
    }

    /**
     * Loads TO_MANY fields for specified entities.
     *
     * @param string $entityClass
     * @param array $entities
     * @param array $fieldsToPreload
     */
    private function loadCollections(string $entityClass, array $entities, array $fieldsToPreload): void
    {
        if (!$entities) {
            return;
        }

        /** @var ClassMetadata $entityMetadata */
        $entityMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $toManyFields = [];

        foreach ($fieldsToPreload as $targetField) {
            $assocMapping = $entityMetadata->getAssociationMapping($targetField);
            if (!($assocMapping['type'] & ClassMetadata::TO_MANY)) {
                continue;
            }

            $toManyFields[] = $targetField;
            $targetFieldItems = $this->getCollectionItems($entities, $entityMetadata, $targetField);
            $indexBy = $assocMapping['indexBy'] ?? null;

            foreach ($targetFieldItems as $targetFieldItem) {
                $collectionOwner = $entities[$targetFieldItem['id']];
                /** @var PersistentCollection $collection */
                $collection = $this->propertyAccessor->getValue($collectionOwner, $targetField);
                if ($this->isCollectionAndNotInitialized($collection)) {
                    $item = $targetFieldItem[0];
                    $unwrappedCollection = $collection->unwrap();
                    if ($indexBy) {
                        $itemKey = $this->propertyAccessor->getValue($item, $indexBy);
                        $unwrappedCollection->set($itemKey, $item);
                    } else {
                        $unwrappedCollection->add($item);
                    }
                }
            }
        }

        foreach ($entities as $entity) {
            foreach ($toManyFields as $targetField) {
                $this->propertyAccessor->getValue($entity, $targetField)->setInitialized(true);
            }
        }
    }

    /**
     * @param array $entities
     * @param ClassMetadata $entityMetadata
     * @param string $targetField
     * @return array
     */
    private function getCollectionItems(array $entities, ClassMetadata $entityMetadata, string $targetField): array
    {
        $entityClass = $entityMetadata->getName();
        $entityIdField = $this->getEntityIdField($entityClass);
        $assocMapping = $entityMetadata->getAssociationMapping($targetField);
        $targetEntityClass = $entityMetadata->getAssociationTargetClass($targetField);
        $targetEntityRepository = $this->doctrineHelper->getEntityRepositoryForClass($targetEntityClass);
        $qbToMany = $targetEntityRepository->createQueryBuilder('collection_item');

        if ($assocMapping['type'] & ClassMetadata::ONE_TO_MANY) {
            $mappedBy = $entityMetadata->getAssociationMappedByTargetField($targetField);
            $qbToMany
                ->addSelect('collection_item_' . $mappedBy . '.' . $entityIdField)
                ->innerJoin('collection_item.' . $mappedBy, 'collection_item_' . $mappedBy)
                ->andWhere($qbToMany->expr()->in('collection_item_' . $mappedBy, ':entities'));
        } elseif ($assocMapping['type'] & ClassMetadata::MANY_TO_MANY) {
            $qbToMany
                ->addSelect('entity.' . $entityIdField)
                ->innerJoin($entityClass, 'entity', Query\Expr\Join::WITH, $qbToMany->expr()->eq(1, 1))
                ->innerJoin('entity.' . $targetField, 'entity_' . $targetField)
                ->andWhere($qbToMany->expr()->eq('entity_' . $targetField, 'collection_item'))
                ->andWhere($qbToMany->expr()->in('entity', ':entities'));
        }

        if (!empty($assocMapping['orderBy'])) {
            foreach ($assocMapping['orderBy'] as $sort => $order) {
                $qbToMany->addOrderBy('collection_item.' . $sort, $order);
            }
        }

        return $qbToMany
            ->setParameter(':entities', array_keys($entities))
            ->getQuery()
            ->execute();
    }

    /**
     * Loads entities by specified ids.
     *
     * @param array $idsToLoadBy
     */
    private function loadByIds(array $idsToLoadBy): void
    {
        if (!$idsToLoadBy) {
            return;
        }

        foreach ($idsToLoadBy as $targetEntityClass => $targetEntitiesIds) {
            /** @var ClassMetadata $targetEntityMetadata */
            $targetEntityMetadata = $this->doctrineHelper->getEntityMetadataForClass($targetEntityClass);
            $targetEntityRepository = $this->doctrineHelper->getEntityRepositoryForClass($targetEntityClass);
            $qb = $targetEntityRepository->createQueryBuilder('target_entity');
            $query = $qb
                ->where($qb->expr()->in('target_entity', ':target_ids'))
                ->setParameter('target_ids', array_keys($targetEntitiesIds))
                ->getQuery();

            if (!$targetEntityMetadata->getAssociationMappings()) {
                $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            }

            $query->execute();
        }
    }

    /**
     * @param object $entity
     * @return bool
     */
    private function isProxyAndNotInitialized(?object $entity): bool
    {
        return $entity instanceof Proxy && !$entity->__isInitialized();
    }

    /**
     * @param object $collection
     * @return bool
     */
    private function isCollectionAndNotInitialized(?object $collection): bool
    {
        return $collection instanceof AbstractLazyCollection && !$collection->isInitialized();
    }
}
