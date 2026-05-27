<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Isolator;

use Closure;
use Doctrine\ORM\UnitOfWork;
use Oro\Component\DraftSession\Entity\DraftSessionAwareInterface;
use Oro\Component\DraftSession\Util\EntityDraftUtils;

/**
 * Extracts and restores non-draft entity and collection scheduling entries in a Doctrine UnitOfWork.
 *
 * This class accesses private Doctrine UnitOfWork internals via Closure::bind.
 * It is tightly coupled to doctrine/orm ~2.7 and must be verified after upgrades.
 */
class NonDraftEntitiesUnitOfWorkIsolator
{
    /**
     * Private UnitOfWork properties that hold scheduled entity-level changes.
     * Values are arrays keyed by spl_object_id with entity objects as values.
     */
    private const array ENTITY_SCHEDULING_PROPERTIES = [
        'entityInsertions',
        'entityUpdates',
        'entityDeletions',
        'orphanRemovals',
    ];

    /**
     * Private UnitOfWork properties that hold scheduled collection-level changes.
     * Values are arrays of PersistentCollection objects.
     */
    private const array COLLECTION_SCHEDULING_PROPERTIES = [
        'collectionUpdates',
        'collectionDeletions',
    ];

    /**
     * Extracts all non-draft entity and collection changes from the UnitOfWork scheduling queues,
     * leaving only draft session entity changes pending.
     *
     * Returns a snapshot that can be applied back to the UnitOfWork via {@see restoreNonDraftEntities()}.
     */
    public function isolateNonDraftEntities(UnitOfWork $unitOfWork): UnitOfWorkSnapshot
    {
        $state = [];

        foreach (self::ENTITY_SCHEDULING_PROPERTIES as $property) {
            $all = $this->readProperty($unitOfWork, $property);
            $drafts = [];
            $nonDrafts = [];

            foreach ($all as $oid => $entity) {
                if ($entity instanceof DraftSessionAwareInterface && EntityDraftUtils::isEntityDraft($entity)) {
                    $drafts[$oid] = $entity;
                } else {
                    $nonDrafts[$oid] = $entity;
                }
            }

            $state[$property] = $nonDrafts;
            $this->writeProperty($unitOfWork, $property, $drafts);
        }

        foreach (self::COLLECTION_SCHEDULING_PROPERTIES as $property) {
            $all = $this->readProperty($unitOfWork, $property);
            $draftCollections = [];
            $nonDraftCollections = [];

            foreach ($all as $key => $collection) {
                $owner = $collection->getOwner();
                if ($owner instanceof DraftSessionAwareInterface && EntityDraftUtils::isEntityDraft($owner)) {
                    $draftCollections[$key] = $collection;
                } else {
                    $nonDraftCollections[$key] = $collection;
                }
            }

            $state[$property] = $nonDraftCollections;
            $this->writeProperty($unitOfWork, $property, $draftCollections);
        }

        return new UnitOfWorkSnapshot($state);
    }

    /**
     * Restores scheduling state previously captured by {@see isolateNonDraftEntities()} back into the UnitOfWork.
     */
    public function restoreNonDraftEntities(UnitOfWork $unitOfWork, UnitOfWorkSnapshot $snapshot): void
    {
        $state = $snapshot->getState();

        foreach (self::ENTITY_SCHEDULING_PROPERTIES as $property) {
            $current = $this->readProperty($unitOfWork, $property);
            // Use array union (+) instead of array_merge to preserve spl_object_id integer keys.
            $this->writeProperty($unitOfWork, $property, $current + ($state[$property] ?? []));
        }

        foreach (self::COLLECTION_SCHEDULING_PROPERTIES as $property) {
            $current = $this->readProperty($unitOfWork, $property);
            $this->writeProperty($unitOfWork, $property, $current + ($state[$property] ?? []));
        }
    }

    /**
     * Reads a private property from the UnitOfWork via Closure::bind.
     */
    private function readProperty(UnitOfWork $unitOfWork, string $property): array
    {
        $reader = Closure::bind(
            static fn (UnitOfWork $uow) => $uow->{$property},
            null,
            UnitOfWork::class
        );

        return $reader($unitOfWork);
    }

    /**
     * Writes a value into a private property of the UnitOfWork via Closure::bind.
     */
    private function writeProperty(UnitOfWork $unitOfWork, string $property, array $value): void
    {
        $writer = Closure::bind(
            static function (UnitOfWork $uow) use ($property, $value): void {
                $uow->{$property} = $value;
            },
            null,
            UnitOfWork::class
        );

        $writer($unitOfWork);
    }
}
