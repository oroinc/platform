<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Consumption\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DraftBundle\Consumption\Extension\DraftableFilterExtension;
use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Bundle\DraftBundle\Manager\DraftableFilterState;
use Oro\Component\MessageQueue\Consumption\Context;

class DraftableFilterExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var DraftableFilterExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->extension = new DraftableFilterExtension(
            $this->managerRegistry,
            $this->createMock(DraftableFilterState::class)
        );
    }

    public function testOnPreReceivedFilterDisabled(): void
    {
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects($this->once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(false);
        $filters->expects($this->never())
            ->method('disable');

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->managerRegistry ->expects($this->once())
            ->method('getManager')
            ->willReturn($em);

        /** @var Context $context */
        $context = $this->createMock(Context::class);

        $this->extension->onPreReceived($context);
    }

    public function testOnPreReceivedFilterEnabled(): void
    {
        $filters = $this->createMock(FilterCollection::class);
        $filters->expects($this->once())
            ->method('isEnabled')
            ->with(DraftableFilter::FILTER_ID)
            ->willReturn(true);
        $filters->expects($this->once())
            ->method('disable')
            ->with(DraftableFilter::FILTER_ID);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getFilters')
            ->willReturn($filters);

        $this->managerRegistry ->expects($this->once())
            ->method('getManager')
            ->willReturn($em);

        /** @var Context $context */
        $context = $this->createMock(Context::class);

        $this->extension->onPreReceived($context);
    }
}
