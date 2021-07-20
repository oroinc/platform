<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Bundle\DraftBundle\Manager\DraftableFilterManager;

class DraftableFilterManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var DraftableFilterManager */
    private $manager;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->manager = new DraftableFilterManager(
            $this->managerRegistry
        );
    }

    public function testDisableFilterDisabled(): void
    {
        $className = 'className';
        /** @var FilterCollection|\PHPUnit\Framework\MockObject\MockObject $filters */
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects($this->once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(false);
        $filters->expects($this->never())
            ->method('disable');

        $this->mockManagerRegistry($filters, $className);

        $this->manager->disable($className);
    }

    public function testDisableFilterEnabled(): void
    {
        $className = 'className';
        /** @var FilterCollection|\PHPUnit\Framework\MockObject\MockObject $filters */
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects($this->once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects($this->once())
            ->method('disable')
            ->with(DraftableFilter::FILTER_ID);

        $this->mockManagerRegistry($filters, $className);

        $this->manager->disable($className);
    }

    public function testEnableFilter(): void
    {
        $className = 'className';
        /** @var FilterCollection|\PHPUnit\Framework\MockObject\MockObject $filters */
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects($this->once())
            ->method('enable')
            ->with(DraftableFilter::FILTER_ID);

        $this->mockManagerRegistry($filters, $className);

        $this->manager->enable($className);
    }

    private function mockManagerRegistry(FilterCollection $filters, string $className): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);
    }
}
