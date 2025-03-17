<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\MaterializedView;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\MaterializedView as MaterializedViewEntity;
use Oro\Bundle\PlatformBundle\Entity\Repository\MaterializedViewEntityRepository;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewManager;
use Oro\Bundle\PlatformBundle\MaterializedView\MaterializedViewRemover;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MaterializedViewRemoverTest extends TestCase
{
    private MaterializedViewManager&MockObject $materializedViewManager;
    private MaterializedViewEntityRepository&MockObject $repository;
    private LoggerInterface&MockObject $logger;
    private MaterializedViewRemover $remover;

    #[\Override]
    protected function setUp(): void
    {
        $this->materializedViewManager = $this->createMock(MaterializedViewManager::class);
        $this->repository = $this->createMock(MaterializedViewEntityRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(MaterializedViewEntity::class)
            ->willReturn($this->repository);

        $this->remover = new MaterializedViewRemover(
            $doctrine,
            $this->materializedViewManager,
            $this->logger
        );
    }

    public function testRemoveOlderThanWhenNothingToRemove(): void
    {
        $daysOld = 5;
        $dateTime = new \DateTime(sprintf('today -%d days', $daysOld));
        $this->repository->expects(self::once())
            ->method('findOlderThan')
            ->with($dateTime)
            ->willReturn([]);

        $this->materializedViewManager->expects(self::never())
            ->method(self::anything());

        $this->logger->expects(self::once())
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
        $this->repository->expects(self::once())
            ->method('findOlderThan')
            ->with($dateTime)
            ->willReturn($materializedViewNames);

        $this->materializedViewManager->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive([$materializedViewNames[0]], [$materializedViewNames[1]]);

        $this->logger->expects(self::once())
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
