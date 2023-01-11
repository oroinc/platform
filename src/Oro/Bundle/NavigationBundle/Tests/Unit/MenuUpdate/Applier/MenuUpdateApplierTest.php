<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Applier;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\MenuUpdateApplier;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\MenuUpdateApplierInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\MenuUpdateToMenuItemPropagatorInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MenuUpdateApplierTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private MenuUpdateToMenuItemPropagatorInterface|\PHPUnit\Framework\MockObject\MockObject
        $menuUpdateToMenuItemPropagator;

    private MenuUpdateApplier $applier;

    protected function setUp(): void
    {
        $this->menuUpdateToMenuItemPropagator = $this->createMock(MenuUpdateToMenuItemPropagatorInterface::class);

        $this->applier = new MenuUpdateApplier($this->menuUpdateToMenuItemPropagator);
    }

    public function testApplyMenuUpdateWhenHasTargetItem(): void
    {
        $menu = $this->getMenu();
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1');
        $menuUpdate->setParentKey('item-2');

        $this->menuUpdateToMenuItemPropagator
            ->expects(self::once())
            ->method('propagateFromMenuUpdate')
            ->with($item, $menuUpdate, MenuUpdateToMenuItemPropagatorInterface::STRATEGY_FULL);

        $context = new MenuUpdateApplierContext($menu);
        self::assertEquals(
            MenuUpdateApplierInterface::RESULT_ITEM_UPDATED,
            $this->applier->applyMenuUpdate($menuUpdate, $menu, [], $context)
        );

        $expectedItem = $this->createItem('item-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));

        self::assertEquals($expectedItem, $item);

        $expectedContext = (new MenuUpdateApplierContext($menu))
            ->addUpdatedItem($expectedItem, $menuUpdate);
        $expectedContext->getMenuItemsByName();

        self::assertEquals($expectedContext, $context);
    }

    public function testApplyMenuUpdateWhenHasTargetItemButNoParent(): void
    {
        $menu = $this->getMenu();
        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1');
        $menuUpdate->setParentKey('non-existing');

        $this->menuUpdateToMenuItemPropagator
            ->expects(self::once())
            ->method('propagateFromMenuUpdate')
            ->with($item, $menuUpdate, MenuUpdateToMenuItemPropagatorInterface::STRATEGY_FULL);

        $context = new MenuUpdateApplierContext($menu);
        self::assertEquals(
            MenuUpdateApplierInterface::RESULT_ITEM_UPDATED | MenuUpdateApplierInterface::RESULT_ITEM_ORPHANED,
            $this->applier->applyMenuUpdate($menuUpdate, $menu, [], $context)
        );

        $expectedItem = $this->createItem('item-1-1-1');
        $expectedItem->setParent($menu->getChild('item-1')->getChild('item-1-1'));

        self::assertEquals($expectedItem, $item);

        $expectedContext = (new MenuUpdateApplierContext($menu))
            ->addUpdatedItem($expectedItem, $menuUpdate)
            ->addOrphanedItem($menuUpdate->getParentKey(), $expectedItem, $menuUpdate);
        $expectedContext->getMenuItemsByName();

        self::assertEquals($expectedContext, $context);
    }

    public function testApplyMenuUpdateWhenNoTargetItem(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('item-2');

        $this->menuUpdateToMenuItemPropagator
            ->expects(self::once())
            ->method('propagateFromMenuUpdate')
            ->with(
                self::isInstanceOf(ItemInterface::class),
                $menuUpdate,
                MenuUpdateToMenuItemPropagatorInterface::STRATEGY_FULL
            );

        $context = new MenuUpdateApplierContext($menu);
        self::assertEquals(
            MenuUpdateApplierInterface::RESULT_ITEM_CREATED | MenuUpdateApplierInterface::RESULT_ITEM_LOST,
            $this->applier->applyMenuUpdate($menuUpdate, $menu, [], $context)
        );

        $expectedItem = $this->createItem('item-1-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));

        self::assertEquals($expectedItem, $menu->getChild('item-2')->getChild('item-1-1-1-1'));

        $expectedContext = (new MenuUpdateApplierContext($menu))
            ->addCreatedItem($expectedItem, $menuUpdate)
            ->addLostItem($expectedItem, $menuUpdate);
        $expectedContext->getMenuItemsByName();

        self::assertEquals($expectedContext, $context);
    }

    public function testApplyMenuUpdateWhenNoTargetItemButIsCustom(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('item-2');
        $menuUpdate->setCustom(true);

        $this->menuUpdateToMenuItemPropagator
            ->expects(self::once())
            ->method('propagateFromMenuUpdate')
            ->with(
                self::isInstanceOf(ItemInterface::class),
                $menuUpdate,
                MenuUpdateToMenuItemPropagatorInterface::STRATEGY_FULL
            );

        $context = new MenuUpdateApplierContext($menu);
        self::assertEquals(
            MenuUpdateApplierInterface::RESULT_ITEM_CREATED,
            $this->applier->applyMenuUpdate($menuUpdate, $menu, [], $context)
        );

        $expectedItem = $this->createItem('item-1-1-1-1');
        $expectedItem->setParent($menu->getChild('item-2'));

        self::assertEquals($expectedItem, $menu->getChild('item-2')->getChild('item-1-1-1-1'));

        $expectedContext = (new MenuUpdateApplierContext($menu))
            ->addCreatedItem($expectedItem, $menuUpdate);
        $expectedContext->getMenuItemsByName();
        self::assertEquals($expectedContext, $context);
    }

    public function testApplyMenuUpdateWhenNoTargetItemAndNoParentButIsCustom(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('non-existing');
        $menuUpdate->setCustom(true);

        $this->menuUpdateToMenuItemPropagator
            ->expects(self::once())
            ->method('propagateFromMenuUpdate')
            ->with(
                self::isInstanceOf(ItemInterface::class),
                $menuUpdate,
                MenuUpdateToMenuItemPropagatorInterface::STRATEGY_FULL
            );

        $context = new MenuUpdateApplierContext($menu);
        self::assertEquals(
            MenuUpdateApplierInterface::RESULT_ITEM_CREATED | MenuUpdateApplierInterface::RESULT_ITEM_ORPHANED,
            $this->applier->applyMenuUpdate($menuUpdate, $menu, [], $context)
        );

        $expectedItem = $this->createItem('item-1-1-1-1');
        $expectedItem->setParent($menu);

        self::assertEquals($expectedItem, $menu->getChild('item-1-1-1-1'));

        $expectedContext = (new MenuUpdateApplierContext($menu))
            ->addCreatedItem($expectedItem, $menuUpdate)
            ->addOrphanedItem($menuUpdate->getParentKey(), $expectedItem, $menuUpdate);
        $expectedContext->getMenuItemsByName();
        self::assertEquals($expectedContext, $context);
    }

    public function testApplyMenuUpdateWhenNoTargetItemAndNoParent(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate->setKey('item-1-1-1-1');
        $menuUpdate->setParentKey('non-existing');

        $this->menuUpdateToMenuItemPropagator
            ->expects(self::once())
            ->method('propagateFromMenuUpdate')
            ->with(
                self::isInstanceOf(ItemInterface::class),
                $menuUpdate,
                MenuUpdateToMenuItemPropagatorInterface::STRATEGY_FULL
            );

        $context = new MenuUpdateApplierContext($menu);
        self::assertEquals(
            MenuUpdateApplierInterface::RESULT_ITEM_CREATED | MenuUpdateApplierInterface::RESULT_ITEM_LOST
            | MenuUpdateApplierInterface::RESULT_ITEM_ORPHANED,
            $this->applier->applyMenuUpdate($menuUpdate, $menu, [], $context)
        );

        $expectedItem = $this->createItem('item-1-1-1-1');
        $expectedItem->setParent($menu);

        self::assertEquals($expectedItem, $menu->getChild('item-1-1-1-1'));

        $expectedContext = (new MenuUpdateApplierContext($menu))
            ->addCreatedItem($expectedItem, $menuUpdate)
            ->addLostItem($expectedItem, $menuUpdate)
            ->addOrphanedItem($menuUpdate->getParentKey(), $expectedItem, $menuUpdate);
        $expectedContext->getMenuItemsByName();
        self::assertEquals($expectedContext, $context);
    }
}
