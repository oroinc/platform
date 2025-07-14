<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Consumption\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Consumption\Extension\DraftableFilterExtension;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Bundle\DraftBundle\Manager\DraftableFilterState;
use Oro\Component\MessageQueue\Consumption\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DraftableFilterExtensionTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private DraftableFilterState&MockObject $filterState;
    private DraftableFilterExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->filterState = $this->createMock(DraftableFilterState::class);

        $this->extension = new DraftableFilterExtension($this->doctrine, $this->filterState);
    }

    public function testOnPreReceivedFilterDisabled(): void
    {
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects(self::once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(false);
        $filters->expects(self::never())
            ->method('disable');

        $this->filterState->expects(self::never())
            ->method('setDisabled');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->doctrine->expects(self::once())
            ->method('getManager')
            ->willReturn($em);

        $this->extension->onPreReceived($this->createMock(Context::class));
    }

    public function testOnPreReceivedFilterEnabled(): void
    {
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects(self::once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects(self::once())
            ->method('disable')
            ->with(DraftableFilter::FILTER_ID);

        $this->filterState->expects(self::once())
            ->method('setDisabled');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->doctrine->expects(self::once())
            ->method('getManager')
            ->willReturn($em);

        $this->extension->onPreReceived($this->createMock(Context::class));
    }
}
