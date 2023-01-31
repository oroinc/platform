<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Propagator\ToMenuItem;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\CompositePropagator;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\MenuUpdateToMenuItemPropagatorInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class CompositePropagatorTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menuItem,
        array $propagators,
        bool $expected
    ): void {
        $compositePropagator = new CompositePropagator($propagators);

        self::assertSame(
            $expected,
            $compositePropagator->isApplicable(
                $menuItem,
                $menuUpdate,
                MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC
            )
        );
    }

    public function isApplicableDataProvider(): array
    {
        $menuUpdate = new MenuUpdateStub();
        $menuItem = $this->createMock(ItemInterface::class);

        $propagator1 = $this->createMock(MenuUpdateToMenuItemPropagatorInterface::class);
        $propagator1
            ->expects(self::any())
            ->method('isApplicable')
            ->with($menuItem, $menuUpdate, MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC)
            ->willReturn(false);

        $propagator2 = $this->createMock(MenuUpdateToMenuItemPropagatorInterface::class);
        $propagator2
            ->expects(self::any())
            ->method('isApplicable')
            ->with($menuItem, $menuUpdate, MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC)
            ->willReturn(true);

        $propagator3 = $this->createMock(MenuUpdateToMenuItemPropagatorInterface::class);
        $propagator3
            ->expects(self::any())
            ->method('isApplicable')
            ->with($menuItem, $menuUpdate, MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC)
            ->willReturn(false);

        return [
            'no propagators' => [
                'menuUpdate' => $menuUpdate,
                'menuItem' => $menuItem,
                'propagators' => [],
                'expected' => false,
            ],
            'no applicable propagators' => [
                'menuUpdate' => $menuUpdate,
                'menuItem' => $menuItem,
                'propagators' => [$propagator1, $propagator3],
                'expected' => false,
            ],
            'has applicable propagator' => [
                'menuUpdate' => $menuUpdate,
                'menuItem' => $menuItem,
                'propagators' => [$propagator1, $propagator2, $propagator3],
                'expected' => true,
            ],
        ];
    }

    public function testPropagateFromMenuUpdateWhenNoPropagators(): void
    {
        $menuUpdate = new MenuUpdateStub();
        $menuItem = $this->createMock(ItemInterface::class);
        $strategy = MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC;

        $this->expectNotToPerformAssertions();

        (new CompositePropagator([]))->propagateFromMenuUpdate($menuItem, $menuUpdate, $strategy);
    }

    public function testPropagateFromMenuUpdate(): void
    {
        $menuUpdate = (new MenuUpdateStub())
            ->setKey('sample_item');
        $menu = $this->createItem('sample_menu');
        $menuItem = $menu->addChild($menuUpdate->getKey());
        $strategy = MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC;

        $propagator1 = $this->createMock(MenuUpdateToMenuItemPropagatorInterface::class);
        $propagator1
            ->expects(self::once())
            ->method('isApplicable')
            ->with($menuItem, $menuUpdate, MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC)
            ->willReturn(false);
        $propagator1
            ->expects(self::never())
            ->method('propagateFromMenuUpdate');

        $propagator2 = $this->createMock(MenuUpdateToMenuItemPropagatorInterface::class);
        $propagator2
            ->expects(self::any())
            ->method('isApplicable')
            ->with($menuItem, $menuUpdate, MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC)
            ->willReturn(true);
        $propagator2
            ->expects(self::once())
            ->method('propagateFromMenuUpdate')
            ->with($menuItem, $menuUpdate, MenuUpdateToMenuItemPropagatorInterface::STRATEGY_BASIC);

        $compositePropagator = new CompositePropagator([$propagator1, $propagator2]);

        $compositePropagator->propagateFromMenuUpdate($menuItem, $menuUpdate, $strategy);
    }
}
