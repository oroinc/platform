<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Isolator;

use Closure;
use Doctrine\ORM\UnitOfWork;
use Oro\Component\DraftSession\Entity\DraftSessionAwareInterface;
use Oro\Component\DraftSession\Util\EntityDraftUtils;

/**
 * Extracts draft entity scheduling entries to prevent them from being flushed alongside non-draft entities,
 * and marks draft entities as read-only so Doctrine skips changeset computation for them.
 * Both operations are reversible via {@see restoreDraftEntities()}.
 *
 * This class accesses private Doctrine UnitOfWork internals via Closure::bind.
 * It is tightly coupled to doctrine/orm ~2.7 and must be verified after upgrades.
 */
class DraftEntitiesUnitOfWorkIsolator
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
     * Extracts draft entity scheduling entries from the UnitOfWork and marks draft entities as read-only.
     *
     * Draft entities remain in the identity map but are marked read-only so Doctrine skips changeset
     * computation and update SQL generation for them during the non-draft flush.
     * Scheduling queues (insertions, updates, deletions, collection changes) are cleared of draft entries
     * to prevent any draft persistence from occurring.
     *
     * Returns a snapshot of the removed draft state that can be applied back via {@see restoreDraftEntities()}.
     */
    public function isolateDraftEntities(UnitOfWork $unitOfWork): UnitOfWorkSnapshot
    {
        $state = [];

        // Collect spl_object_ids of all draft entities present in the identity map.
        $draftOids = $this->collectDraftOids($this->readProperty($unitOfWork, 'identityMap'));

        // Scheduling queues: extract draft entries and leave non-draft entries in place.
        foreach (self::ENTITY_SCHEDULING_PROPERTIES as $property) {
            $all = $this->readProperty($unitOfWork, $property);
            $state[$property] = array_intersect_key($all, $draftOids);
            $this->writeProperty($unitOfWork, $property, array_diff_key($all, $draftOids));
        }

        foreach (self::COLLECTION_SCHEDULING_PROPERTIES as $property) {
            $state[$property] = $this->isolateCollectionSchedulingProperty($unitOfWork, $property);
        }

        $state['scheduledForSynchronization'] = $this->isolateScheduledForSynchronization($unitOfWork);
        $state['newlyReadOnlyOids'] = $this->markDraftEntitiesAsReadOnly($unitOfWork, $draftOids);

        return new UnitOfWorkSnapshot($state);
    }

    /**
     * Restores draft entity state previously captured by {@see isolateDraftEntities()} back into the UnitOfWork.
     *
     * Merges the scheduling snapshot entries back into the UnitOfWork and removes the read-only flags
     * that were added for draft entities during {@see isolateDraftEntities()}.
     */
    public function restoreDraftEntities(UnitOfWork $unitOfWork, UnitOfWorkSnapshot $snapshot): void
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

        if (!empty($state['scheduledForSynchronization'])) {
            $current = $this->readProperty($unitOfWork, 'scheduledForSynchronization');
            foreach ($state['scheduledForSynchronization'] as $className => $collections) {
                foreach ($collections as $oid => $collection) {
                    $current[$className][$oid] = $collection;
                }
            }
            $this->writeProperty($unitOfWork, 'scheduledForSynchronization', $current);
        }

        // Remove the read-only flags that were added by clearDraftEntities().
        if (!empty($state['newlyReadOnlyOids'])) {
            $readOnlyObjects = $this->readProperty($unitOfWork, 'readOnlyObjects');
            foreach (array_keys($state['newlyReadOnlyOids']) as $oid) {
                unset($readOnlyObjects[$oid]);
            }
            $this->writeProperty($unitOfWork, 'readOnlyObjects', $readOnlyObjects);
        }
    }

    /**
     * Returns a map of spl_object_id => true for all draft entities found in the given identity map.
     *
     * @param array<string, array<string, object>> $identityMap
     *
     * @return array<int, true>
     */
    private function collectDraftOids(array $identityMap): array
    {
        $draftOids = [];
        foreach ($identityMap as $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof DraftSessionAwareInterface && EntityDraftUtils::isEntityDraft($entity)) {
                    $draftOids[spl_object_id($entity)] = true;
                }
            }
        }

        return $draftOids;
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

    /**
     * Partitions a flat collection scheduling queue into draft-owned and non-draft-owned entries.
     * Writes the non-draft collections back into the UoW and returns the draft collections for snapshotting.
     *
     * @return array<mixed, object> Draft-owned collections keyed by their original queue key.
     */
    private function isolateCollectionSchedulingProperty(UnitOfWork $unitOfWork, string $property): array
    {
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

        $this->writeProperty($unitOfWork, $property, $nonDraftCollections);

        return $draftCollections;
    }

    /**
     * Partitions scheduledForSynchronization (structured as [className => [oid => collection]])
     * into draft-owned and non-draft-owned groups.
     * Writes the non-draft entries back into the UoW and returns the draft entries for snapshotting.
     *
     * @return array<string, array<mixed, object>> Draft-owned entries grouped by entity class name.
     */
    private function isolateScheduledForSynchronization(UnitOfWork $unitOfWork): array
    {
        $scheduledForSync = $this->readProperty($unitOfWork, 'scheduledForSynchronization');
        $draftScheduledForSync = [];
        $cleanScheduledForSync = [];

        foreach ($scheduledForSync as $className => $collections) {
            foreach ($collections as $oid => $collection) {
                $owner = $collection->getOwner();
                if ($owner instanceof DraftSessionAwareInterface && EntityDraftUtils::isEntityDraft($owner)) {
                    $draftScheduledForSync[$className][$oid] = $collection;
                } else {
                    $cleanScheduledForSync[$className][$oid] = $collection;
                }
            }
        }

        $this->writeProperty($unitOfWork, 'scheduledForSynchronization', $cleanScheduledForSync);

        return $draftScheduledForSync;
    }

    /**
     * Marks all draft entities found via $draftOids as read-only in the UnitOfWork.
     * Only OIDs not already flagged are tracked so pre-existing flags are preserved on restore.
     *
     * @param array<int, true> $draftOids Map of spl_object_id => true for all draft entities.
     *
     * @return array<int, true> OIDs that were newly marked read-only during this call.
     */
    private function markDraftEntitiesAsReadOnly(UnitOfWork $unitOfWork, array $draftOids): array
    {
        $readOnlyObjects = $this->readProperty($unitOfWork, 'readOnlyObjects');
        $newlyReadOnlyOids = [];

        foreach ($draftOids as $oid => $_) {
            if (!isset($readOnlyObjects[$oid])) {
                $newlyReadOnlyOids[$oid] = true;
                $readOnlyObjects[$oid] = true;
            }
        }

        $this->writeProperty($unitOfWork, 'readOnlyObjects', $readOnlyObjects);

        return $newlyReadOnlyOids;
    }
}
