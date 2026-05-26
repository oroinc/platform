<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Isolator;

use Doctrine\ORM\UnitOfWork;
use Oro\Component\DraftSession\Isolator\DraftEntitiesUnitOfWorkIsolator;
use Oro\Component\DraftSession\Isolator\UnitOfWorkSnapshot;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use Oro\Component\DraftSession\Tests\Unit\Stub\PersistentCollectionOwnerStub;
use PHPUnit\Framework\TestCase;

final class DraftEntitiesUnitOfWorkIsolatorTest extends TestCase
{
    private DraftEntitiesUnitOfWorkIsolator $isolator;

    #[\Override]
    protected function setUp(): void
    {
        $this->isolator = new DraftEntitiesUnitOfWorkIsolator();
    }

    public function testIsolateDraftEntitiesWithEmptyUnitOfWorkReturnsEmptySnapshot(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $snapshot = $this->isolator->isolateDraftEntities($unitOfWork);
        $state = $snapshot->getState();

        self::assertSame([], $state['entityInsertions']);
        self::assertSame([], $state['entityUpdates']);
        self::assertSame([], $state['entityDeletions']);
        self::assertSame([], $state['orphanRemovals']);
        self::assertSame([], $state['collectionUpdates']);
        self::assertSame([], $state['collectionDeletions']);
        self::assertSame([], $state['scheduledForSynchronization']);
        self::assertSame([], $state['newlyReadOnlyOids']);
    }

    /**
     * @dataProvider entitySchedulingPropertyProvider
     */
    public function testIsolateDraftEntitiesExtractsDraftEntitySchedulingEntryToSnapshot(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftOid = spl_object_id($draft);
        $nonDraftOid = spl_object_id($nonDraftEntity);

        $unitOfWork = $this->createMock(UnitOfWork::class);

        // Draft must be in the identity map for its OID to be detected.
        $this->writeUoWProperty($unitOfWork, 'identityMap', [
            EntityDraftAwareStub::class => ['1' => $draft, '2' => $nonDraftEntity],
        ]);
        $this->writeUoWProperty($unitOfWork, $property, [
            $draftOid => $draft,
            $nonDraftOid => $nonDraftEntity,
        ]);

        $snapshot = $this->isolator->isolateDraftEntities($unitOfWork);

        self::assertSame([$draftOid => $draft], $snapshot->getState()[$property]);
    }

    /**
     * @dataProvider entitySchedulingPropertyProvider
     */
    public function testIsolateDraftEntitiesLeavesNonDraftEntitySchedulingEntryInUnitOfWork(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftOid = spl_object_id($draft);
        $nonDraftOid = spl_object_id($nonDraftEntity);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'identityMap', [
            EntityDraftAwareStub::class => ['1' => $draft, '2' => $nonDraftEntity],
        ]);
        $this->writeUoWProperty($unitOfWork, $property, [
            $draftOid => $draft,
            $nonDraftOid => $nonDraftEntity,
        ]);

        $this->isolator->isolateDraftEntities($unitOfWork);

        self::assertSame([$nonDraftOid => $nonDraftEntity], $this->readUoWProperty($unitOfWork, $property));
    }

    /**
     * @dataProvider collectionSchedulingPropertyProvider
     */
    public function testIsolateDraftEntitiesExtractsDraftCollectionSchedulingEntryToSnapshot(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftCollection = new PersistentCollectionOwnerStub($draft);
        $nonDraftCollection = new PersistentCollectionOwnerStub($nonDraftEntity);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'identityMap', []);
        $this->writeUoWProperty($unitOfWork, $property, [
            'draft' => $draftCollection,
            'non-draft' => $nonDraftCollection,
        ]);

        $snapshot = $this->isolator->isolateDraftEntities($unitOfWork);

        self::assertSame(['draft' => $draftCollection], $snapshot->getState()[$property]);
    }

    /**
     * @dataProvider collectionSchedulingPropertyProvider
     */
    public function testIsolateDraftEntitiesLeavesNonDraftCollectionSchedulingEntryInUnitOfWork(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftCollection = new PersistentCollectionOwnerStub($draft);
        $nonDraftCollection = new PersistentCollectionOwnerStub($nonDraftEntity);
        $noOwnerCollection = new PersistentCollectionOwnerStub(null);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'identityMap', []);
        $this->writeUoWProperty($unitOfWork, $property, [
            'draft' => $draftCollection,
            'non-draft' => $nonDraftCollection,
            'no-owner' => $noOwnerCollection,
        ]);

        $this->isolator->isolateDraftEntities($unitOfWork);

        self::assertSame(
            ['non-draft' => $nonDraftCollection, 'no-owner' => $noOwnerCollection],
            $this->readUoWProperty($unitOfWork, $property)
        );
    }

    public function testIsolateDraftEntitiesExtractsDraftCollectionFromScheduledForSynchronization(): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $draftCollection = new PersistentCollectionOwnerStub($draft);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'identityMap', []);
        $this->writeUoWProperty($unitOfWork, 'scheduledForSynchronization', [
            EntityDraftAwareStub::class => ['oid-1' => $draftCollection],
        ]);

        $snapshot = $this->isolator->isolateDraftEntities($unitOfWork);

        self::assertSame(
            [EntityDraftAwareStub::class => ['oid-1' => $draftCollection]],
            $snapshot->getState()['scheduledForSynchronization']
        );
    }

    public function testIsolateDraftEntitiesLeavesNonDraftCollectionInScheduledForSynchronization(): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftCollection = new PersistentCollectionOwnerStub($draft);
        $nonDraftCollection = new PersistentCollectionOwnerStub($nonDraftEntity);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'identityMap', []);
        $this->writeUoWProperty($unitOfWork, 'scheduledForSynchronization', [
            EntityDraftAwareStub::class => [
                'draft-oid' => $draftCollection,
                'non-draft-oid' => $nonDraftCollection,
            ],
        ]);

        $this->isolator->isolateDraftEntities($unitOfWork);

        self::assertSame(
            [EntityDraftAwareStub::class => ['non-draft-oid' => $nonDraftCollection]],
            $this->readUoWProperty($unitOfWork, 'scheduledForSynchronization')
        );
    }

    public function testIsolateDraftEntitiesMarksIdentityMapDraftEntitiesAsReadOnly(): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftOid = spl_object_id($draft);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'identityMap', [
            EntityDraftAwareStub::class => ['1' => $draft, '2' => $nonDraftEntity],
        ]);

        $snapshot = $this->isolator->isolateDraftEntities($unitOfWork);

        $readOnlyObjects = $this->readUoWProperty($unitOfWork, 'readOnlyObjects');
        self::assertArrayHasKey($draftOid, $readOnlyObjects);
        self::assertArrayNotHasKey(spl_object_id($nonDraftEntity), $readOnlyObjects);
        self::assertSame([$draftOid => true], $snapshot->getState()['newlyReadOnlyOids']);
    }

    public function testIsolateDraftEntitiesDoesNotOverwritePreexistingReadOnlyFlags(): void
    {
        $alreadyReadOnlyDraft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $alreadyReadOnlyOid = spl_object_id($alreadyReadOnlyDraft);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'identityMap', [
            EntityDraftAwareStub::class => ['1' => $alreadyReadOnlyDraft],
        ]);
        // Mark the entity as read-only before isolation runs.
        $this->writeUoWProperty($unitOfWork, 'readOnlyObjects', [$alreadyReadOnlyOid => true]);

        $snapshot = $this->isolator->isolateDraftEntities($unitOfWork);

        // The pre-existing flag must remain, but must NOT be tracked as newly added.
        self::assertArrayHasKey($alreadyReadOnlyOid, $this->readUoWProperty($unitOfWork, 'readOnlyObjects'));
        self::assertArrayNotHasKey($alreadyReadOnlyOid, $snapshot->getState()['newlyReadOnlyOids']);
    }

    /**
     * @dataProvider entitySchedulingPropertyProvider
     */
    public function testRestoreDraftEntitiesMergesEntitySnapshotBackIntoUnitOfWork(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftOid = spl_object_id($draft);
        $nonDraftOid = spl_object_id($nonDraftEntity);

        // Simulate the state after draft isolation: only non-draft entity remains in UoW queue.
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, [$nonDraftOid => $nonDraftEntity]);

        $snapshot = new UnitOfWorkSnapshot([$property => [$draftOid => $draft]]);

        $this->isolator->restoreDraftEntities($unitOfWork, $snapshot);

        self::assertSame(
            [$nonDraftOid => $nonDraftEntity, $draftOid => $draft],
            $this->readUoWProperty($unitOfWork, $property)
        );
    }

    /**
     * @dataProvider collectionSchedulingPropertyProvider
     */
    public function testRestoreDraftEntitiesMergesCollectionSnapshotBackIntoUnitOfWork(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftCollection = new PersistentCollectionOwnerStub($draft);
        $nonDraftCollection = new PersistentCollectionOwnerStub($nonDraftEntity);

        // Simulate the state after draft isolation: only non-draft-owned collection remains in UoW queue.
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, ['non-draft' => $nonDraftCollection]);

        $snapshot = new UnitOfWorkSnapshot([$property => ['draft' => $draftCollection]]);

        $this->isolator->restoreDraftEntities($unitOfWork, $snapshot);

        self::assertSame(
            ['non-draft' => $nonDraftCollection, 'draft' => $draftCollection],
            $this->readUoWProperty($unitOfWork, $property)
        );
    }

    public function testRestoreDraftEntitiesRestoresScheduledForSynchronization(): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $draftCollection = new PersistentCollectionOwnerStub($draft);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'scheduledForSynchronization', []);

        $snapshot = new UnitOfWorkSnapshot([
            'scheduledForSynchronization' => [
                EntityDraftAwareStub::class => ['oid-1' => $draftCollection],
            ],
        ]);

        $this->isolator->restoreDraftEntities($unitOfWork, $snapshot);

        self::assertSame(
            [EntityDraftAwareStub::class => ['oid-1' => $draftCollection]],
            $this->readUoWProperty($unitOfWork, 'scheduledForSynchronization')
        );
    }

    public function testRestoreDraftEntitiesRemovesNewlyAddedReadOnlyFlags(): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $draftOid = spl_object_id($draft);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'readOnlyObjects', [$draftOid => true]);

        $snapshot = new UnitOfWorkSnapshot(['newlyReadOnlyOids' => [$draftOid => true]]);

        $this->isolator->restoreDraftEntities($unitOfWork, $snapshot);

        self::assertArrayNotHasKey($draftOid, $this->readUoWProperty($unitOfWork, 'readOnlyObjects'));
    }

    public function testRestoreDraftEntitiesPreservesPreexistingReadOnlyFlags(): void
    {
        $preexistingEntity = new EntityDraftAwareStub();
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');

        $preexistingOid = spl_object_id($preexistingEntity);
        $draftOid = spl_object_id($draft);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'readOnlyObjects', [
            $preexistingOid => true,
            $draftOid => true,
        ]);

        // Only the draft OID was newly added — the preexisting entity OID was already there before.
        $snapshot = new UnitOfWorkSnapshot(['newlyReadOnlyOids' => [$draftOid => true]]);

        $this->isolator->restoreDraftEntities($unitOfWork, $snapshot);

        $readOnlyObjects = $this->readUoWProperty($unitOfWork, 'readOnlyObjects');
        self::assertArrayHasKey($preexistingOid, $readOnlyObjects);
        self::assertArrayNotHasKey($draftOid, $readOnlyObjects);
    }

    public function testRestoreDraftEntitiesWithEmptySnapshotDoesNotModifyUnitOfWork(): void
    {
        $nonDraftEntity = new EntityDraftAwareStub();
        $nonDraftOid = spl_object_id($nonDraftEntity);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'entityInsertions', [$nonDraftOid => $nonDraftEntity]);

        $this->isolator->restoreDraftEntities($unitOfWork, new UnitOfWorkSnapshot([]));

        self::assertSame(
            [$nonDraftOid => $nonDraftEntity],
            $this->readUoWProperty($unitOfWork, 'entityInsertions')
        );
    }

    /**
     * @dataProvider entitySchedulingPropertyProvider
     */
    public function testIsolateAndRestoreProducesOriginalEntitySchedulingState(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftOid = spl_object_id($draft);
        $nonDraftOid = spl_object_id($nonDraftEntity);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'identityMap', [
            EntityDraftAwareStub::class => ['1' => $draft, '2' => $nonDraftEntity],
        ]);
        $this->writeUoWProperty($unitOfWork, $property, [
            $draftOid => $draft,
            $nonDraftOid => $nonDraftEntity,
        ]);

        $snapshot = $this->isolator->isolateDraftEntities($unitOfWork);
        $this->isolator->restoreDraftEntities($unitOfWork, $snapshot);

        $result = $this->readUoWProperty($unitOfWork, $property);
        self::assertCount(2, $result);
        self::assertSame($draft, $result[$draftOid]);
        self::assertSame($nonDraftEntity, $result[$nonDraftOid]);
    }

    /**
     * @dataProvider collectionSchedulingPropertyProvider
     */
    public function testIsolateAndRestoreProducesOriginalCollectionSchedulingState(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftCollection = new PersistentCollectionOwnerStub($draft);
        $nonDraftCollection = new PersistentCollectionOwnerStub($nonDraftEntity);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, 'identityMap', []);
        $this->writeUoWProperty($unitOfWork, $property, [
            'draft' => $draftCollection,
            'non-draft' => $nonDraftCollection,
        ]);

        $snapshot = $this->isolator->isolateDraftEntities($unitOfWork);
        $this->isolator->restoreDraftEntities($unitOfWork, $snapshot);

        $result = $this->readUoWProperty($unitOfWork, $property);
        self::assertCount(2, $result);
        self::assertSame($draftCollection, $result['draft']);
        self::assertSame($nonDraftCollection, $result['non-draft']);
    }

    public static function entitySchedulingPropertyProvider(): iterable
    {
        yield 'entityInsertions' => ['entityInsertions'];
        yield 'entityUpdates' => ['entityUpdates'];
        yield 'entityDeletions' => ['entityDeletions'];
        yield 'orphanRemovals' => ['orphanRemovals'];
    }

    public static function collectionSchedulingPropertyProvider(): iterable
    {
        yield 'collectionUpdates' => ['collectionUpdates'];
        yield 'collectionDeletions' => ['collectionDeletions'];
    }

    /**
     * Reads a private property from the UnitOfWork via Closure::bind.
     */
    private function readUoWProperty(UnitOfWork $unitOfWork, string $property): array
    {
        $reader = \Closure::bind(
            static fn (UnitOfWork $uow) => $uow->{$property},
            null,
            UnitOfWork::class
        );

        return $reader($unitOfWork);
    }

    /**
     * Writes a value into a private property of the UnitOfWork via Closure::bind.
     */
    private function writeUoWProperty(UnitOfWork $unitOfWork, string $property, array $value): void
    {
        $writer = \Closure::bind(
            static function (UnitOfWork $uow) use ($property, $value): void {
                $uow->{$property} = $value;
            },
            null,
            UnitOfWork::class
        );

        $writer($unitOfWork);
    }
}
