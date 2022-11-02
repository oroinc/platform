<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\MaterializedView;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView as MaterializedViewEntity;
use Oro\Bundle\PlatformBundle\Entity\Repository\MaterializedViewEntityRepository;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewRemover;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class MaterializedViewRemoverTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private MaterializedViewManager|\PHPUnit\Framework\MockObject\MockObject $materializedViewManager;

    private MaterializedViewRemover $remover;

    private MaterializedViewEntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->materializedViewManager = $this->createMock(MaterializedViewManager::class);

        $this->remover = new MaterializedViewRemover($managerRegistry, $this->materializedViewManager);

        $this->setUpLoggerMock($this->remover);

        $this->repository = $this->createMock(MaterializedViewEntityRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(MaterializedViewEntity::class)
            ->willReturn($this->repository);
    }

    public function testRemoveOlderThanWhenNothingToRemove(): void
    {
        $daysOld = 5;
        $dateTime = new \DateTime(sprintf('today -%d days', $daysOld));
        $this->repository
            ->expects(self::once())
            ->method('findOlderThan')
            ->with($dateTime)
            ->willReturn([]);

        $this->materializedViewManager
            ->expects(self::never())
            ->method(self::anything());

        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                'Found {count} materialized view older than {daysOld} days for removal.',
                [
                    'count' => 0,
                    'daysOld' => $daysOld,
                    'materializedViewNames' => [],
                ]
            );

        self::assertEquals([], $this->remover->removeOlderThan($daysOld));
    }

    public function testRemoveOlderThan(): void
    {
        $daysOld = 5;
        $dateTime = new \DateTime(sprintf('today -%d days', $daysOld));
        $materializedViewNames = ['sample_name1', 'sample_name2'];
        $this->repository
            ->expects(self::once())
            ->method('findOlderThan')
            ->with($dateTime)
            ->willReturn($materializedViewNames);

        $this->materializedViewManager
            ->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive([$materializedViewNames[0]], [$materializedViewNames[1]]);

        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                'Found {count} materialized view older than {daysOld} days for removal.',
                [
                    'count' => count($materializedViewNames),
                    'daysOld' => $daysOld,
                    'materializedViewNames' => $materializedViewNames,
                ]
            );

        self::assertEquals($materializedViewNames, $this->remover->removeOlderThan($daysOld));
    }
}
