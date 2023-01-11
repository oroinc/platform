<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Event\MenuUpdatesApplyAfterEvent;
use Oro\Bundle\NavigationBundle\Menu\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\MenuUpdateApplierInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MenuUpdateBuilderTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

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

    public function testWhenNotDisplayed(): void
    {
        $menu = $this->createItem('sample_menu')
            ->setDisplay(false);

        $this->menuUpdateProvider
            ->expects(self::never())
            ->method('getMenuUpdatesForMenuItem');

        $this->builder->build($menu);
    }

    public function testBuildWhenNoMenuUpdates(): void
    {
        $menu = $this->createItem('sample_menu');
        $this->menuUpdateProvider
            ->expects(self::once())
            ->method('getMenuUpdatesForMenuItem')
            ->with($menu)
            ->willReturn([]);

        $this->menuUpdateApplier
            ->expects(self::never())
            ->method(self::anything());

        $this->eventDispatcher
            ->expects(self::never())
            ->method(self::anything());

        $this->builder->build($menu);
    }

    public function testBuildWhenHasMenuUpdates(): void
    {
        $menu = $this->createItem('sample_menu')
            ->addChild('sample_item');
        $menuUpdate1 = $this->createMock(MenuUpdateInterface::class);
        $menuUpdate2 = $this->createMock(MenuUpdateInterface::class);

        $options = ['sample_key' => 'sample_value'];
        $this->menuUpdateProvider
            ->expects(self::once())
            ->method('getMenuUpdatesForMenuItem')
            ->with($menu)
            ->willReturn([$menuUpdate1, $menuUpdate2]);

        $context = new MenuUpdateApplierContext($menu);
        $this->menuUpdateApplier
            ->expects(self::exactly(2))
            ->method('applyMenuUpdate')
            ->withConsecutive(
                [$menuUpdate1, $menu, $options, $context],
                [$menuUpdate2, $menu, $options, $context]
            )
            ->willReturn(MenuUpdateApplierInterface::RESULT_ITEM_CREATED);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(new MenuUpdatesApplyAfterEvent($context));

        $this->builder->build($menu, $options);
    }
}
