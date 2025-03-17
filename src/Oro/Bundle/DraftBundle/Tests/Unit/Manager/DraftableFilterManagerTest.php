<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Bundle\DraftBundle\Manager\DraftableFilterManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DraftableFilterManagerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private DraftableFilterManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->manager = new DraftableFilterManager($this->doctrine);
    }

    private function expectGetFilters(FilterCollection $filters, string $className): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($entityManager);
    }

    public function testDisableFilterDisabled(): void
    {
        $className = 'className';
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects(self::once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(false);
        $filters->expects(self::never())
            ->method('disable');

        $this->expectGetFilters($filters, $className);

        $this->manager->disable($className);
    }

    public function testDisableFilterEnabled(): void
    {
        $className = 'className';
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects(self::once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects(self::once())
            ->method('disable')
            ->with(DraftableFilter::FILTER_ID);

        $this->expectGetFilters($filters, $className);

        $this->manager->disable($className);
    }

    public function testEnableFilter(): void
    {
        $className = 'className';
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects(self::once())
            ->method('enable')
            ->with(DraftableFilter::FILTER_ID);

        $this->expectGetFilters($filters, $className);

        $this->manager->enable($className);
    }
}
