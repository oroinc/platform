<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Isolator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Component\DraftSession\Isolator\DraftEntitiesEntityManagerIsolator;
use Oro\Component\DraftSession\Isolator\NonDraftEntitiesUnitOfWorkIsolator;
use Oro\Component\DraftSession\Isolator\UnitOfWorkSnapshot;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DraftEntitiesEntityManagerIsolatorTest extends TestCase
{
    private NonDraftEntitiesUnitOfWorkIsolator&MockObject $nonDraftEntitiesUnitOfWorkIsolator;

    private EntityManagerInterface&MockObject $entityManager;

    private UnitOfWork&MockObject $unitOfWork;

    private DraftEntitiesEntityManagerIsolator $isolator;

    #[\Override]
    protected function setUp(): void
    {
        $this->nonDraftEntitiesUnitOfWorkIsolator = $this->createMock(NonDraftEntitiesUnitOfWorkIsolator::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);

        $this->entityManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $this->isolator = new DraftEntitiesEntityManagerIsolator($this->nonDraftEntitiesUnitOfWorkIsolator);
    }

    public function testFlushDraftEntitiesIsolatesNonDraftEntitiesFlushesAndRestores(): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('draft-uuid-1');
        $snapshot = new UnitOfWorkSnapshot([]);

        $this->unitOfWork
            ->method('getIdentityMap')
            ->willReturn([
                EntityDraftAwareStub::class => [$draft],
            ]);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('isolateNonDraftEntities')
            ->with(self::identicalTo($this->unitOfWork))
            ->willReturn($snapshot);

        $this->entityManager
            ->expects(self::once())
            ->method('flush')
            ->with([$draft]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('restoreNonDraftEntities')
            ->with(self::identicalTo($this->unitOfWork), self::identicalTo($snapshot));

        $this->isolator->flushDraftEntities($this->entityManager);
    }

    public function testFlushDraftEntitiesExcludesNonDraftEntitiesFromIdentityMap(): void
    {
        $nonDraftEntity = new EntityDraftAwareStub();
        $snapshot = new UnitOfWorkSnapshot([]);

        $this->unitOfWork
            ->method('getIdentityMap')
            ->willReturn([
                EntityDraftAwareStub::class => [$nonDraftEntity],
            ]);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('isolateNonDraftEntities')
            ->willReturn($snapshot);

        // Flush is called with null since there are no draft entities to flush.
        $this->entityManager
            ->expects(self::once())
            ->method('flush')
            ->with(null);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('restoreNonDraftEntities');

        $this->isolator->flushDraftEntities($this->entityManager);
    }

    public function testFlushDraftEntitiesIgnoresNonDraftSessionAwareEntitiesInIdentityMap(): void
    {
        $snapshot = new UnitOfWorkSnapshot([]);

        $this->unitOfWork
            ->method('getIdentityMap')
            ->willReturn([
                // stdClass does not implement DraftSessionAwareInterface.
                \stdClass::class => [new \stdClass()],
            ]);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('isolateNonDraftEntities')
            ->willReturn($snapshot);

        // Flush is called with null since there are no draft entities to flush.
        $this->entityManager
            ->expects(self::once())
            ->method('flush')
            ->with(null);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('restoreNonDraftEntities');

        $this->isolator->flushDraftEntities($this->entityManager);
    }

    public function testFlushDraftEntitiesIncludesDraftEntitiesFromScheduledInsertions(): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('insertion-draft-uuid');
        $snapshot = new UnitOfWorkSnapshot([]);

        $this->unitOfWork
            ->method('getIdentityMap')
            ->willReturn([]);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([$draft]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('isolateNonDraftEntities')
            ->willReturn($snapshot);

        $this->entityManager
            ->expects(self::once())
            ->method('flush')
            ->with([$draft]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('restoreNonDraftEntities');

        $this->isolator->flushDraftEntities($this->entityManager);
    }

    public function testFlushDraftEntitiesExcludesNonDraftEntitiesFromScheduledInsertions(): void
    {
        $nonDraftEntity = new EntityDraftAwareStub();
        $snapshot = new UnitOfWorkSnapshot([]);

        $this->unitOfWork
            ->method('getIdentityMap')
            ->willReturn([]);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([$nonDraftEntity]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('isolateNonDraftEntities')
            ->willReturn($snapshot);

        // Flush is called with null since the only insertion is not a draft.
        $this->entityManager
            ->expects(self::once())
            ->method('flush')
            ->with(null);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('restoreNonDraftEntities');

        $this->isolator->flushDraftEntities($this->entityManager);
    }

    public function testFlushDraftEntitiesCollectsDraftEntitiesFromBothIdentityMapAndScheduledInsertions(): void
    {
        $draftFromMap = (new EntityDraftAwareStub())->setDraftSessionUuid('map-draft-uuid');
        $nonDraftFromMap = new EntityDraftAwareStub();
        $draftFromInsertions = (new EntityDraftAwareStub())->setDraftSessionUuid('insertion-draft-uuid');
        $nonDraftFromInsertions = new EntityDraftAwareStub();
        $snapshot = new UnitOfWorkSnapshot([]);

        $this->unitOfWork
            ->method('getIdentityMap')
            ->willReturn([
                EntityDraftAwareStub::class => [$draftFromMap, $nonDraftFromMap],
            ]);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([$draftFromInsertions, $nonDraftFromInsertions]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('isolateNonDraftEntities')
            ->willReturn($snapshot);

        // Only the two draft entities should be flushed.
        $this->entityManager
            ->expects(self::once())
            ->method('flush')
            ->with([$draftFromMap, $draftFromInsertions]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('restoreNonDraftEntities');

        $this->isolator->flushDraftEntities($this->entityManager);
    }

    public function testFlushDraftEntitiesRestoresNonDraftEntitiesEvenWhenFlushThrows(): void
    {
        $draft = (new EntityDraftAwareStub())->setDraftSessionUuid('draft-uuid');
        $snapshot = new UnitOfWorkSnapshot([]);

        $this->unitOfWork
            ->method('getIdentityMap')
            ->willReturn([
                EntityDraftAwareStub::class => [$draft],
            ]);

        $this->unitOfWork
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('isolateNonDraftEntities')
            ->willReturn($snapshot);

        $flushException = new \RuntimeException('Flush failed');
        $this->entityManager
            ->expects(self::once())
            ->method('flush')
            ->willThrowException($flushException);

        // Restore must still be called even when flush throws.
        $this->nonDraftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('restoreNonDraftEntities')
            ->with(self::identicalTo($this->unitOfWork), self::identicalTo($snapshot));

        $this->expectExceptionObject($flushException);

        $this->isolator->flushDraftEntities($this->entityManager);
    }
}
