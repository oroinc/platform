<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Isolator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Component\DraftSession\Isolator\DraftEntitiesUnitOfWorkIsolator;
use Oro\Component\DraftSession\Isolator\NonDraftEntitiesEntityManagerIsolator;
use Oro\Component\DraftSession\Isolator\UnitOfWorkSnapshot;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NonDraftEntitiesEntityManagerIsolatorTest extends TestCase
{
    private DraftEntitiesUnitOfWorkIsolator&MockObject $draftEntitiesUnitOfWorkIsolator;

    private NonDraftEntitiesEntityManagerIsolator $isolator;

    #[\Override]
    protected function setUp(): void
    {
        $this->draftEntitiesUnitOfWorkIsolator = $this->createMock(DraftEntitiesUnitOfWorkIsolator::class);
        $this->isolator = new NonDraftEntitiesEntityManagerIsolator($this->draftEntitiesUnitOfWorkIsolator);
    }

    public function testFlushNonDraftEntitiesIsolatesDraftEntitiesFlushesAndRestores(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $snapshot = new UnitOfWorkSnapshot(['entityInsertions' => []]);

        $entityManager
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->draftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('isolateDraftEntities')
            ->with(self::identicalTo($unitOfWork))
            ->willReturn($snapshot);

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $this->draftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('restoreDraftEntities')
            ->with(self::identicalTo($unitOfWork), self::identicalTo($snapshot));

        $this->isolator->flushNonDraftEntities($entityManager);
    }

    public function testFlushNonDraftEntitiesRestoresDraftEntitiesEvenWhenFlushThrows(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $snapshot = new UnitOfWorkSnapshot([]);

        $entityManager
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        $this->draftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('isolateDraftEntities')
            ->with(self::identicalTo($unitOfWork))
            ->willReturn($snapshot);

        $flushException = new \RuntimeException('Flush failed');
        $entityManager
            ->expects(self::once())
            ->method('flush')
            ->willThrowException($flushException);

        $this->draftEntitiesUnitOfWorkIsolator
            ->expects(self::once())
            ->method('restoreDraftEntities')
            ->with(self::identicalTo($unitOfWork), self::identicalTo($snapshot));

        $this->expectExceptionObject($flushException);

        $this->isolator->flushNonDraftEntities($entityManager);
    }
}
