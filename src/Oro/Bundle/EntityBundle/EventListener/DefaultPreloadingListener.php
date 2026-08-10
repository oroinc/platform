<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\EntityBundle\Event\PreloadEntityEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
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

    private bool $stopPropagation = false;

    /** @var array */
    private $entityIdField = [];

    public function __construct(DoctrineHelper $doctrineHelper, PropertyAccessorInterface $propertyAccessor)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param bool $stopPropagation Whether this listener should stop propagation.
     */
    public function setStopPropagation(bool $stopPropagation): void
    {
        $this->stopPropagation = $stopPropagation;
    }

    public function onPreload(PreloadEntityEvent $preloadEntityEvent): void
    {
        $mainEntities = $preloadEntityEvent->getEntities();
        $firstEntity = current($mainEntities);
        if (!$firstEntity) {
            return;
        }

        // Main entities class.
        $mainEntityClass = $this->doctrineHelper->getEntityClass($firstEntity);

        $entityIdField = $this->getEntityIdField($mainEntityClass);

        // Contains uninitialized proxied main entities.
        $mainEntitiesToLoad = [];

        // Collects uninitialized main entities.
        foreach ($mainEntities as $mainEntity) {
            if ($this->isProxyAndNotInitialized($mainEntity)) {
                $entityId = $this->propertyAccessor->getValue($mainEntity, $entityIdField);
                $mainEntitiesToLoad[$entityId] = $mainEntity;
            }
        }

        // Loads uninitialized main entities.
        $this->loadMainEntities($mainEntityClass, $mainEntitiesToLoad, $preloadEntityEvent->getFieldsToPreload());

        // Contains main entities whose collections from TO_MANY associations should be preloaded.
        $mainEntitiesByFields = [];

        // Contains not initialized proxied entities from TO_ONE associations of main entities.
        $targetEntitiesByIds = [];

        /** @var ClassMetadata $mainEntityMetadata */
        $mainEntityMetadata = $this->doctrineHelper->getEntityMetadataForClass($mainEntityClass);

        // Sorts out TO_ONE and TO_MANY relations to load from main entities.
        foreach ($mainEntities as $mainEntity) {
            if ($this->isProxyAndNotInitialized($mainEntity)) {
                // Skips entities which failed to initialize - they might not exist.
                continue;
            }

            $this->processRelations(
                $preloadEntityEvent,
                $mainEntityMetadata,
                $mainEntity,
                $targetEntitiesByIds,
                $mainEntitiesByFields
            );
        }

        $this->loadToManyRelations($mainEntityClass, $mainEntitiesByFields);
        $this->loadToOneRelationsByIds($targetEntitiesByIds);

        if ($this->stopPropagation) {
            $preloadEntityEvent->stopPropagation();
        }
    }

    /**
     * Sorts out entities to preload from TO_ONE and TO_MANY relations.
     */
    private function processRelations(
        PreloadEntityEvent $preloadEntityEvent,
        ClassMetadata $mainEntityMetadata,
        object $mainEntity,
        array &$targetEntitiesByIds,
        array &$entitiesToLoadCollections
    ): void {
        $entityIdField = $this->getEntityIdField($mainEntityMetadata->getName());
        $entityId = $this->propertyAccessor->getValue($mainEntity, $entityIdField);

        foreach ($preloadEntityEvent->getFieldsToPreload() as $targetField) {
            $assocType = $mainEntityMetadata->getAssociationMapping($targetField)['type'];
            $targetFieldValue = $this->propertyAccessor->getValue($mainEntity, $targetField);
            if ($assocType & ClassMetadata::TO_ONE) {
                $this->processToOneRelation(
                    $preloadEntityEvent,
                    $mainEntityMetadata,
                    $mainEntity,
                    $entityId,
                    $targetField,
                    $targetFieldValue,
                    $targetEntitiesByIds
                );
                continue;
            }

            $this->processToManyRelation(
                $preloadEntityEvent,
                $mainEntityMetadata,
                $mainEntity,
                $entityId,
                $targetField,
                $targetFieldValue,
                $entitiesToLoadCollections
            );
        }
    }

    /**
     * Gets target entity from $fieldName of main entity and puts it to $targetEntitiesByIds if it is not initialized.
     */
    private function processToOneRelation(
        PreloadEntityEvent $preloadEntityEvent,
        ClassMetadata $mainEntityMetadata,
        object $mainEntity,
        $mainEntityId,
        string $fieldName,
        $fieldValue,
        array &$targetEntitiesByIds
    ): void {
        if (!$this->isProxyAndNotInitialized($fieldValue) || $preloadEntityEvent->hasSubFields($fieldName)) {
            return;
        }

        $targetEntityClass = $mainEntityMetadata->getAssociationTargetClass($fieldName);
        $targetEntityIdField = $this->getEntityIdField($targetEntityClass);
        $targetEntityId = $this->propertyAccessor->getValue($fieldValue, $targetEntityIdField);
        $targetEntitiesByIds[$targetEntityClass][$targetEntityId] = $fieldValue;
    }

    /**
     * Puts main entity to $mainEntitiesByFields if it has a collection to preload.
     */
    private function processToManyRelation(
        PreloadEntityEvent $preloadEntityEvent,
        ClassMetadata $mainEntityMetadata,
        object $mainEntity,
        $mainEntityId,
        string $fieldName,
        $fieldValue,
        array &$mainEntitiesByFields
    ): void {
        if (!$this->isCollectionAndNotInitialized($fieldValue)) {
            return;
        }

        $mainEntitiesByFields[$fieldName][$mainEntityId] = $mainEntity;
    }

    private function getEntityIdField(string $entityClass): string
    {
        if (!isset($this->entityIdField[$entityClass])) {
            $this->entityIdField[$entityClass] = $this->doctrineHelper
                ->getSingleEntityIdentifierFieldName($entityClass);
        }

        return $this->entityIdField[$entityClass];
    }

    /**
     * Loads uninitialized main entities.
     * Additonally loads TO_ONE relations if any.
     */
    private function loadMainEntities(string $mainEntityClass, array $mainEntities, array $fieldsToPreload): void
    {
        if (!$mainEntities) {
            return;
        }

        $mainEntityRepository = $this->doctrineHelper->getEntityRepositoryForClass($mainEntityClass);
        /** @var ClassMetadata $mainEntityMetadata */
        $mainEntityMetadata = $this->doctrineHelper->getEntityMetadataForClass($mainEntityClass);

        $qb = $mainEntityRepository->createQueryBuilder('entity');
        $qb
            ->where($qb->expr()->in('entity', ':entities'))
            ->setParameter('entities', array_keys($mainEntities));

        foreach ($fieldsToPreload as $targetField) {
            $assocType = $mainEntityMetadata->getAssociationMapping($targetField)['type'];
            if ($assocType & ClassMetadata::TO_ONE) {
                $qb
                    ->addSelect('entity_' . $targetField)
                    ->leftJoin('entity.' . $targetField, 'entity_' . $targetField);
            }
        }

        $qb->getQuery()->execute();
    }

    /**
     * Loads TO_MANY relations for specified entities.
     */
    private function loadToManyRelations(string $mainEntityClass, array $mainEntitiesByFields): void
    {
        if (!$mainEntitiesByFields) {
            return;
        }

        /** @var ClassMetadata $mainEntityMetadata */
        $mainEntityMetadata = $this->doctrineHelper->getEntityMetadataForClass($mainEntityClass);

        foreach ($mainEntitiesByFields as $targetField => $mainEntities) {
            $assocMapping = $mainEntityMetadata->getAssociationMapping($targetField);
            if (!($assocMapping['type'] & ClassMetadata::TO_MANY)) {
                continue;
            }

            $targetFieldItems = $this->getCollectionItems($mainEntities, $mainEntityMetadata, $targetField);
            $indexBy = $assocMapping['indexBy'] ?? null;

            foreach ($targetFieldItems as $targetFieldItem) {
                $collectionOwners = $this->getCollectionOwners($targetFieldItem, $mainEntities);

                foreach ($collectionOwners as $collectionOwner) {
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

            foreach ($mainEntities as $entity) {
                $this->propertyAccessor->getValue($entity, $targetField)->setInitialized(true);
            }
        }
    }

    /**
     * Resolves the main entities that own the given collection item, supporting both a single owner
     * ("entity_id") and multiple owners of a many-to-many relation ("entity_ids").
     *
     * @param array $targetFieldItem
     * @param array $mainEntities
     *
     * @return array
     */
    private function getCollectionOwners(array $targetFieldItem, array $mainEntities): array
    {
        if (isset($targetFieldItem['entity_id'])) {
            return [$mainEntities[$targetFieldItem['entity_id']]];
        }

        if (isset($targetFieldItem['entity_ids'])) {
            $collectionOwnerIds = json_decode($targetFieldItem['entity_ids'], true, 2, JSON_THROW_ON_ERROR);

            return array_intersect_key($mainEntities, array_flip($collectionOwnerIds));
        }

        throw new \LogicException('Collection owner id(s) not found in query result');
    }

    private function getCollectionItems(
        array $mainEntities,
        ClassMetadata $mainEntityMetadata,
        string $targetField
    ): array {
        $mainEntityClass = $mainEntityMetadata->getName();
        $mainEntityIdField = $this->getEntityIdField($mainEntityClass);
        $assocMapping = $mainEntityMetadata->getAssociationMapping($targetField);
        $targetEntityClass = $mainEntityMetadata->getAssociationTargetClass($targetField);
        $targetEntityIdField = $this->getEntityIdField($targetEntityClass);
        $targetEntityRepository = $this->doctrineHelper->getEntityRepositoryForClass($targetEntityClass);
        $qbToMany = $targetEntityRepository->createQueryBuilder('collection_item');

        QueryBuilderUtil::checkParameter($mainEntityIdField);

        if ($assocMapping['type'] & ClassMetadata::ONE_TO_MANY) {
            // A single owner per collection item is selected as "entity_id".
            $mappedBy = $mainEntityMetadata->getAssociationMappedByTargetField($targetField);
            QueryBuilderUtil::checkParameter($mappedBy);
            $select = QueryBuilderUtil::sprintf('collection_item_%s.%s as entity_id', $mappedBy, $mainEntityIdField);

            $qbToMany
                ->addSelect($select)
                ->innerJoin('collection_item.' . $mappedBy, 'collection_item_' . $mappedBy)
                ->andWhere($qbToMany->expr()->in('collection_item_' . $mappedBy, ':entities'));
        } else {
            // A collection item may be shared by several owners, aggregated as "entity_ids".
            QueryBuilderUtil::checkParameter($mainEntityClass);
            $select = QueryBuilderUtil::sprintf('JSON_AGG(entity.%s) as entity_ids', $mainEntityIdField);

            $qbToMany
                ->addSelect($select)
                ->innerJoin($mainEntityClass, 'entity', Query\Expr\Join::WITH, $qbToMany->expr()->eq(1, 1))
                ->innerJoin('entity.' . $targetField, 'entity_' . $targetField)
                ->andWhere($qbToMany->expr()->eq('entity_' . $targetField, 'collection_item'))
                ->andWhere($qbToMany->expr()->in('entity', ':entities'))
                ->addGroupBy(QueryBuilderUtil::sprintf('collection_item.%s', $targetEntityIdField));
        }

        $this->applyOrderBy($qbToMany, $assocMapping);
        $qbToMany->setParameter(':entities', array_keys($mainEntities));

        return $qbToMany->getQuery()->execute();
    }

    private function applyOrderBy(QueryBuilder $qbToMany, array $assocMapping): void
    {
        if (empty($assocMapping['orderBy'])) {
            return;
        }

        foreach ($assocMapping['orderBy'] as $sort => $order) {
            QueryBuilderUtil::checkParameter($sort);
            QueryBuilderUtil::checkParameter($order);
            $qbToMany->addOrderBy('collection_item.' . $sort, $order);
        }
    }

    /**
     * Loads entities by specified ids.
     */
    private function loadToOneRelationsByIds(array $idsToLoadBy): void
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

    private function isProxyAndNotInitialized(?object $entity): bool
    {
        return $entity instanceof Proxy && !$entity->__isInitialized();
    }

    private function isCollectionAndNotInitialized(?object $collection): bool
    {
        return $collection instanceof AbstractLazyCollection && !$collection->isInitialized();
    }
}
