<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Isolator;

use Doctrine\ORM\UnitOfWork;
use Oro\Component\DraftSession\Isolator\NonDraftEntitiesUnitOfWorkIsolator;
use Oro\Component\DraftSession\Isolator\UnitOfWorkSnapshot;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use Oro\Component\DraftSession\Tests\Unit\Stub\PersistentCollectionOwnerStub;
use PHPUnit\Framework\TestCase;

final class NonDraftEntitiesUnitOfWorkIsolatorTest extends TestCase
{
    private NonDraftEntitiesUnitOfWorkIsolator $isolator;

    #[\Override]
    protected function setUp(): void
    {
        $this->isolator = new NonDraftEntitiesUnitOfWorkIsolator();
    }

    public function testIsolateNonDraftEntitiesWithEmptyUnitOfWorkReturnsEmptySnapshot(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);

        $snapshot = $this->isolator->isolateNonDraftEntities($unitOfWork);
        $state = $snapshot->getState();

        self::assertSame([], $state['entityInsertions']);
        self::assertSame([], $state['entityUpdates']);
        self::assertSame([], $state['entityDeletions']);
        self::assertSame([], $state['orphanRemovals']);
        self::assertSame([], $state['collectionUpdates']);
        self::assertSame([], $state['collectionDeletions']);
    }

    /**
     * @dataProvider entitySchedulingPropertyProvider
     */
    public function testIsolateNonDraftEntitiesExtractsNonDraftEntityToSnapshot(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();
        $plainObject = new \stdClass();

        $draftOid = spl_object_id($draft);
        $nonDraftOid = spl_object_id($nonDraftEntity);
        $plainOid = spl_object_id($plainObject);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, [
            $draftOid => $draft,
            $nonDraftOid => $nonDraftEntity,
            $plainOid => $plainObject,
        ]);

        $snapshot = $this->isolator->isolateNonDraftEntities($unitOfWork);

        self::assertSame(
            [$nonDraftOid => $nonDraftEntity, $plainOid => $plainObject],
            $snapshot->getState()[$property]
        );
    }

    /**
     * @dataProvider entitySchedulingPropertyProvider
     */
    public function testIsolateNonDraftEntitiesKeepsDraftEntityInUnitOfWork(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftOid = spl_object_id($draft);
        $nonDraftOid = spl_object_id($nonDraftEntity);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, [
            $draftOid => $draft,
            $nonDraftOid => $nonDraftEntity,
        ]);

        $this->isolator->isolateNonDraftEntities($unitOfWork);

        self::assertSame(
            [$draftOid => $draft],
            $this->readUoWProperty($unitOfWork, $property)
        );
    }

    /**
     * @dataProvider collectionSchedulingPropertyProvider
     */
    public function testIsolateNonDraftEntitiesExtractsNonDraftCollectionToSnapshot(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftCollection = new PersistentCollectionOwnerStub($draft);
        $nonDraftCollection = new PersistentCollectionOwnerStub($nonDraftEntity);
        $noOwnerCollection = new PersistentCollectionOwnerStub(null);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, [
            'draft' => $draftCollection,
            'non-draft' => $nonDraftCollection,
            'no-owner' => $noOwnerCollection,
        ]);

        $snapshot = $this->isolator->isolateNonDraftEntities($unitOfWork);

        self::assertSame(
            ['non-draft' => $nonDraftCollection, 'no-owner' => $noOwnerCollection],
            $snapshot->getState()[$property]
        );
    }

    /**
     * @dataProvider collectionSchedulingPropertyProvider
     */
    public function testIsolateNonDraftEntitiesKeepsDraftCollectionInUnitOfWork(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftCollection = new PersistentCollectionOwnerStub($draft);
        $nonDraftCollection = new PersistentCollectionOwnerStub($nonDraftEntity);

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, [
            'draft' => $draftCollection,
            'non-draft' => $nonDraftCollection,
        ]);

        $this->isolator->isolateNonDraftEntities($unitOfWork);

        self::assertSame(
            ['draft' => $draftCollection],
            $this->readUoWProperty($unitOfWork, $property)
        );
    }

    /**
     * @dataProvider entitySchedulingPropertyProvider
     */
    public function testRestoreNonDraftEntitiesMergesEntitySnapshotBackIntoUnitOfWork(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftOid = spl_object_id($draft);
        $nonDraftOid = spl_object_id($nonDraftEntity);

        // Simulate the state after isolation: only the draft entity remains in the UoW queue.
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, [$draftOid => $draft]);

        $snapshot = new UnitOfWorkSnapshot([$property => [$nonDraftOid => $nonDraftEntity]]);

        $this->isolator->restoreNonDraftEntities($unitOfWork, $snapshot);

        self::assertSame(
            [$draftOid => $draft, $nonDraftOid => $nonDraftEntity],
            $this->readUoWProperty($unitOfWork, $property)
        );
    }

    /**
     * @dataProvider collectionSchedulingPropertyProvider
     */
    public function testRestoreNonDraftEntitiesMergesCollectionSnapshotBackIntoUnitOfWork(string $property): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('session-uuid');
        $nonDraftEntity = new EntityDraftAwareStub();

        $draftCollection = new PersistentCollectionOwnerStub($draft);
        $nonDraftCollection = new PersistentCollectionOwnerStub($nonDraftEntity);

        // Simulate the state after isolation: only the draft-owned collection remains in the UoW queue.
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, ['draft' => $draftCollection]);

        $snapshot = new UnitOfWorkSnapshot([$property => ['non-draft' => $nonDraftCollection]]);

        $this->isolator->restoreNonDraftEntities($unitOfWork, $snapshot);

        self::assertSame(
            ['draft' => $draftCollection, 'non-draft' => $nonDraftCollection],
            $this->readUoWProperty($unitOfWork, $property)
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

        $original = [$draftOid => $draft, $nonDraftOid => $nonDraftEntity];

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, $original);

        $snapshot = $this->isolator->isolateNonDraftEntities($unitOfWork);
        $this->isolator->restoreNonDraftEntities($unitOfWork, $snapshot);

        self::assertSame($original, $this->readUoWProperty($unitOfWork, $property));
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

        $original = ['draft' => $draftCollection, 'non-draft' => $nonDraftCollection];

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->writeUoWProperty($unitOfWork, $property, $original);

        $snapshot = $this->isolator->isolateNonDraftEntities($unitOfWork);
        $this->isolator->restoreNonDraftEntities($unitOfWork, $snapshot);

        self::assertSame($original, $this->readUoWProperty($unitOfWork, $property));
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
