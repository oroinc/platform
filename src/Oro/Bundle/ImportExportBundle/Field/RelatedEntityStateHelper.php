<?php

namespace Oro\Bundle\ImportExportBundle\Field;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Store and revert related entity states to prevent detached and unknown entities to be flushed during commit.
 */
class RelatedEntityStateHelper
{
    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var array
     */
    private $rememberedCollectionItems = [];

    /**
     * @var array
     */
    private $collectionSnapshotItems = [];

    public function __construct(
        FieldHelper $fieldHelper,
        DoctrineHelper $doctrineHelper
    ) {
        $this->fieldHelper = $fieldHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function clear()
    {
        $this->rememberedCollectionItems = [];
        $this->collectionSnapshotItems = [];
    }

    /**
     * Remember collection items that were loaded and moved to snapshot during import.
     * These items are present in identity map and are not detached on main entity detach
     * Which may lead to new entity error.
     * Example: Product has collection of Product Variants. Product Variant has relation to Product.
     * On validation failure Product is detached, and all Variant within the collection are detached, but
     * variants that are not in the collection but was loaded to snapshot are still in the UoW identity map
     * and will participate in commit process, so detached product will be found which will cause an exception.
     *
     * @param object $existingEntity
     */
    public function rememberAlteredCollectionsItems($existingEntity)
    {
        $classMetadata = $this->doctrineHelper->getEntityMetadata($existingEntity);
        $relations = $this->fieldHelper->getRelations($classMetadata->getName(), true, true, false);

        foreach ($relations as $relation) {
            $fieldName = $relation['name'];
            $assoc = $classMetadata->getAssociationMapping($fieldName);
            if (empty($assoc['isCascadeDetach'])) {
                continue;
            }
            if ($this->fieldHelper->isMultipleRelation($relation)) {
                $loadedEntitiesCollection = $this->fieldHelper->getObjectValue($existingEntity, $fieldName);
                if ($loadedEntitiesCollection instanceof PersistentCollection) {
                    $snapshot = $loadedEntitiesCollection->getSnapshot();
                    if (count($snapshot)) {
                        foreach ($snapshot as $item) {
                            $this->rememberCollectionRelation($existingEntity, $fieldName, $item);
                        }
                        $this->collectionSnapshotItems[] = $snapshot;
                    }
                }
            }
        }
    }

    /**
     * Remember entities that are added to collections during import.
     *
     * @param object $collectionHolder
     * @param string $collectionFieldName
     * @param object $item
     */
    public function rememberCollectionRelation($collectionHolder, $collectionFieldName, $item)
    {
        if (!$collectionFieldName || !$collectionHolder || !$item) {
            return;
        }

        $this->rememberedCollectionItems[] = [$collectionHolder, $collectionFieldName, $item];
    }

    public function revertRelations()
    {
        $this->removeRememberedCollectionItems();
        $this->forgetLoadedCollectionItems();
        $this->clear();
    }

    private function removeRememberedCollectionItems()
    {
        foreach ($this->rememberedCollectionItems as $relationInfo) {
            [$object, $fieldName, $entity] = $relationInfo;
            $collection = $this->fieldHelper->getObjectValue($object, $fieldName);
            $this->removeEntityFromCollection($entity, $collection);
        }
    }

    /**
     * @param object $entity
     * @param Collection $collection
     */
    private function removeEntityFromCollection($entity, Collection $collection)
    {
        if ($collection->contains($entity)) {
            // remove entity from related entity's updated collections
            if ($collection instanceof PersistentCollection) {
                $association = $collection->getMapping();
                if (is_a($entity, $association['targetEntity'])) {
                    // fix `orphanRemoval` association
                    $tmpAssociation = $association;
                    $tmpAssociation['orphanRemoval'] = false;
                    $collection->setOwner($collection->getOwner(), $tmpAssociation);
                    $collection->removeElement($entity);
                    $collection->setOwner($collection->getOwner(), $association);
                }
            } else {
                $collection->removeElement($entity);
            }
        }
    }

    /**
     * Remove entities that were loaded to the collection
     * but immediately replaced during import from UoW identity map.
     */
    private function forgetLoadedCollectionItems()
    {
        foreach ($this->collectionSnapshotItems as $collection) {
            foreach ($collection as $item) {
                $this->doctrineHelper->getEntityManager($item)->getUnitOfWork()->removeFromIdentityMap($item);
            }
        }
    }
}
