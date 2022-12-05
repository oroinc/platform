<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Oro\Bundle\NavigationBundle\Menu\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\MenuUpdateApplierInterface;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\Model\MenuUpdatesApplyResult;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MenuUpdateBuilderTest extends \PHPUnit\Framework\TestCase
{
    private MenuUpdateProviderInterface|\PHPUnit\Framework\MockObject\MockObject $menuUpdateProvider;

    private MenuUpdateApplierInterface|\PHPUnit\Framework\MockObject\MockObject $menuUpdateApplier;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private MenuUpdateBuilder $builder;

    protected function setUp(): void
    {
        $this->menuUpdateProvider = $this->createMock(MenuUpdateProviderInterface::class);
        $this->menuUpdateApplier = $this->createMock(MenuUpdateApplierInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->builder = new MenuUpdateBuilder(
            $this->menuUpdateProvider,
            $this->menuUpdateApplier,
            $this->eventDispatcher
        );
    }

    public function testBuildWhenNoMenuUpdates(): void
    {
        $menuItem = $this->createMock(ItemInterface::class);
        $this->menuUpdateProvider
            ->expects(self::once())
            ->method('getMenuUpdatesForMenuItem')
            ->with($menuItem)
            ->willReturn([]);

        $this->menuUpdateApplier
            ->expects(self::never())
            ->method(self::anything());

        $this->eventDispatcher
            ->expects(self::never())
            ->method(self::anything());

        $this->builder->build($menuItem);
    }

    public function testBuildWhenMenuUpdates(): void
    {
        $menuItem = $this->createMock(ItemInterface::class);
        $menuUpdates = [$this->createMock(MenuUpdateInterface::class)];
        $options = ['sample_key' => 'sample_value'];
        $this->menuUpdateProvider
            ->expects(self::once())
            ->method('getMenuUpdatesForMenuItem')
            ->with($menuItem)
            ->willReturn($menuUpdates);

        $menuUpdatesApplyResult = $this->createMock(MenuUpdatesApplyResult::class);
        $this->menuUpdateApplier
            ->expects(self::once())
            ->method('applyMenuUpdates')
            ->with($menuItem, $menuUpdates, $options)
            ->willReturn($menuUpdatesApplyResult);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(new MenuUpdatesApplyAfterEvent($menuUpdatesApplyResult));

        $this->builder->build($menuItem, $options);
    }
}
