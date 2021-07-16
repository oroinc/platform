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

    /** @var array */
    private $entityIdField = [];

    public function __construct(DoctrineHelper $doctrineHelper, PropertyAccessorInterface $propertyAccessor)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
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
                $collectionOwner = $mainEntities[$targetFieldItem['id']];
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

            foreach ($mainEntities as $entity) {
                $this->propertyAccessor->getValue($entity, $targetField)->setInitialized(true);
            }
        }
    }

    private function getCollectionItems(
        array $mainEntities,
        ClassMetadata $mainEntityMetadata,
        string $targetField
    ): array {
        $assocMapping = $mainEntityMetadata->getAssociationMapping($targetField);
        if ($assocMapping['type'] & ClassMetadata::ONE_TO_MANY) {
            $qbToMany = $this->getOneToManyQueryBuilder($mainEntityMetadata, $mainEntities, $targetField);
        } elseif ($assocMapping['type'] & ClassMetadata::MANY_TO_MANY) {
            $qbToMany = $this->getManyToManyQueryBuilder($mainEntityMetadata, $mainEntities, $targetField);
        } else {
            throw new \LogicException(sprintf('Target field %s was expected to be a TO_MANY relation', $targetField));
        }

        return $qbToMany->getQuery()->execute();
    }

    private function getOneToManyQueryBuilder(
        ClassMetadata $mainEntityMetadata,
        array $mainEntities,
        string $fieldName
    ): QueryBuilder {
        $mainEntityClass = $mainEntityMetadata->getName();
        $mainEntityIdField = $this->getEntityIdField($mainEntityClass);
        $assocMapping = $mainEntityMetadata->getAssociationMapping($fieldName);
        $targetEntityClass = $mainEntityMetadata->getAssociationTargetClass($fieldName);
        $targetEntityRepository = $this->doctrineHelper->getEntityRepositoryForClass($targetEntityClass);
        $qbToMany = $targetEntityRepository->createQueryBuilder('collection_item');

        $mappedBy = $mainEntityMetadata->getAssociationMappedByTargetField($fieldName);
        QueryBuilderUtil::checkParameter($mappedBy);
        QueryBuilderUtil::checkParameter($mainEntityIdField);
        $qbToMany
            ->addSelect('collection_item_' . $mappedBy . '.' . $mainEntityIdField)
            ->innerJoin('collection_item.' . $mappedBy, 'collection_item_' . $mappedBy)
            ->andWhere($qbToMany->expr()->in('collection_item_' . $mappedBy, ':entities'));

        if (!empty($assocMapping['orderBy'])) {
            foreach ($assocMapping['orderBy'] as $sort => $order) {
                QueryBuilderUtil::checkParameter($sort);
                QueryBuilderUtil::checkParameter($order);
                $qbToMany->addOrderBy('collection_item.' . $sort, $order);
            }
        }

        $qbToMany->setParameter(':entities', array_keys($mainEntities));

        return $qbToMany;
    }

    private function getManyToManyQueryBuilder(
        ClassMetadata $mainEntityMetadata,
        array $mainEntities,
        string $fieldName
    ): QueryBuilder {
        $mainEntityClass = $mainEntityMetadata->getName();
        $mainEntityIdField = $this->getEntityIdField($mainEntityClass);
        $assocMapping = $mainEntityMetadata->getAssociationMapping($fieldName);
        $targetEntityClass = $mainEntityMetadata->getAssociationTargetClass($fieldName);
        $targetEntityRepository = $this->doctrineHelper->getEntityRepositoryForClass($targetEntityClass);
        $qbToMany = $targetEntityRepository->createQueryBuilder('collection_item');

        QueryBuilderUtil::checkParameter($mainEntityClass);
        QueryBuilderUtil::checkParameter($mainEntityIdField);

        $qbToMany
            ->addSelect('entity.' . $mainEntityIdField)
            ->innerJoin($mainEntityClass, 'entity', Query\Expr\Join::WITH, $qbToMany->expr()->eq(1, 1))
            ->innerJoin('entity.' . $fieldName, 'entity_' . $fieldName)
            ->andWhere($qbToMany->expr()->eq('entity_' . $fieldName, 'collection_item'))
            ->andWhere($qbToMany->expr()->in('entity', ':entities'));

        if (!empty($assocMapping['orderBy'])) {
            foreach ($assocMapping['orderBy'] as $sort => $order) {
                QueryBuilderUtil::checkParameter($sort);
                QueryBuilderUtil::checkParameter($order);
                $qbToMany->addOrderBy('collection_item.' . $sort, $order);
            }
        }

        $qbToMany->setParameter(':entities', array_keys($mainEntities));

        return $qbToMany;
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
