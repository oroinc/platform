<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Applier\Model;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\Model\MenuUpdateApplierContext;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MenuUpdateApplierContextTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    public function testGetMenu(): void
    {
        $menu = $this->getMenu();

        self::assertSame($menu, (new MenuUpdateApplierContext($menu))->getMenu());
    }

    public function testGetMenuItemsByName(): void
    {
        $menu = $this->getMenu();

        self::assertEquals(
            MenuUpdateUtils::flattenMenuItem($menu),
            (new MenuUpdateApplierContext($menu))->getMenuItemsByName()
        );
    }

    public function testGetMenuItemByName(): void
    {
        $menu = $this->getMenu();

        self::assertEquals(
            MenuUpdateUtils::flattenMenuItem($menu)['item-2'],
            (new MenuUpdateApplierContext($menu))->getMenuItemByName('item-2')
        );
    }

    public function testGetCreatedItems(): void
    {
        $menu = $this->getMenu();

        $context = (new MenuUpdateApplierContext($menu))
            ->addCreatedItem($menu->getChild('item-1'), $this->createMock(MenuUpdateInterface::class));

        self::assertEquals(
            ['item-1' => $menu->getChild('item-1')],
            $context->getCreatedItems()
        );
    }

    public function testGetCreatedItemsMenuUpdates(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $context = (new MenuUpdateApplierContext($menu))
            ->addCreatedItem($menu->getChild('item-1'), $menuUpdate);

        self::assertEquals(
            ['item-1' => [$menuUpdate->getId() => $menuUpdate]],
            $context->getCreatedItemsMenuUpdates()
        );
    }

    public function testIsCreatedItem(): void
    {
        $menu = $this->getMenu();

        $context = (new MenuUpdateApplierContext($menu))
            ->addCreatedItem($menu->getChild('item-1'), $this->createMock(MenuUpdateInterface::class));

        self::assertTrue($context->isCreatedItem('item-1'));
        self::assertFalse($context->isCreatedItem('item-2'));
    }

    public function testGetUpdatedItems(): void
    {
        $menu = $this->getMenu();

        $context = (new MenuUpdateApplierContext($menu))
            ->addUpdatedItem($menu->getChild('item-1'), $this->createMock(MenuUpdateInterface::class));

        self::assertEquals(
            ['item-1' => $menu->getChild('item-1')],
            $context->getUpdatedItems()
        );
    }

    public function testGetUpdatedItemsMenuUpdates(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $context = (new MenuUpdateApplierContext($menu))
            ->addUpdatedItem($menu->getChild('item-1'), $menuUpdate);

        self::assertEquals(
            ['item-1' => [$menuUpdate->getId() => $menuUpdate]],
            $context->getUpdatedItemsMenuUpdates()
        );
    }

    public function testIsUpdatedItem(): void
    {
        $menu = $this->getMenu();

        $context = (new MenuUpdateApplierContext($menu))
            ->addUpdatedItem($menu->getChild('item-1'), $this->createMock(MenuUpdateInterface::class));

        self::assertTrue($context->isUpdatedItem('item-1'));
        self::assertFalse($context->isUpdatedItem('item-2'));
    }

    public function testGetOrphanedItems(): void
    {
        $menu = $this->getMenu();

        $parentMenuItemName = 'parent_name';
        $context = (new MenuUpdateApplierContext($menu))
            ->addOrphanedItem(
                $parentMenuItemName,
                $menu->getChild('item-1'),
                $this->createMock(MenuUpdateInterface::class)
            );

        self::assertEquals(
            [$parentMenuItemName => ['item-1' => $menu->getChild('item-1')]],
            $context->getOrphanedItems()
        );
    }

    public function testGetOrphanedItemsWhenSpecifiedParentName(): void
    {
        $menu = $this->getMenu();

        $parentMenuItemName = 'parent_name';
        $context = (new MenuUpdateApplierContext($menu))
            ->addOrphanedItem(
                $parentMenuItemName,
                $menu->getChild('item-1'),
                $this->createMock(MenuUpdateInterface::class)
            );

        self::assertEquals(
            ['item-1' => $menu->getChild('item-1')],
            $context->getOrphanedItems($parentMenuItemName)
        );
    }

    public function testGetOrphanedItemsMenuUpdatesWhenSpecifiedParentName(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $parentMenuItemName = 'parent_name';
        $context = (new MenuUpdateApplierContext($menu))
            ->addOrphanedItem($parentMenuItemName, $menu->getChild('item-1'), $menuUpdate);

        self::assertEquals(
            ['item-1' => [$menuUpdate->getId() => $menuUpdate]],
            $context->getOrphanedItemsMenuUpdates($parentMenuItemName)
        );
    }

    public function testRemoveOrphanedItems(): void
    {
        $menu = $this->getMenu();

        $parentMenuItemName = 'parent_name';
        $context = (new MenuUpdateApplierContext($menu))
            ->addOrphanedItem(
                $parentMenuItemName,
                $menu->getChild('item-1'),
                $this->createMock(MenuUpdateInterface::class)
            )
            ->addOrphanedItem(
                $parentMenuItemName,
                $menu->getChild('item-2'),
                $this->createMock(MenuUpdateInterface::class)
            );

        self::assertEquals(
            [$parentMenuItemName => ['item-1' => $menu->getChild('item-1'), 'item-2' => $menu->getChild('item-2')]],
            $context->getOrphanedItems()
        );

        $context->removeOrphanedItem($parentMenuItemName, 'item-1');

        self::assertEquals(
            [$parentMenuItemName => ['item-2' => $menu->getChild('item-2')]],
            $context->getOrphanedItems()
        );
    }

    public function testGetLostItems(): void
    {
        $menu = $this->getMenu();

        $context = (new MenuUpdateApplierContext($menu))
            ->addLostItem($menu->getChild('item-1'), $this->createMock(MenuUpdateInterface::class));

        self::assertEquals(
            ['item-1' => $menu->getChild('item-1')],
            $context->getLostItems()
        );
    }

    public function testGetLostItemsMenuUpdates(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $context = (new MenuUpdateApplierContext($menu))
            ->addLostItem($menu->getChild('item-1'), $menuUpdate);

        self::assertEquals(
            ['item-1' => [$menuUpdate->getId() => $menuUpdate]],
            $context->getLostItemsMenuUpdates()
        );
    }

    public function testRemoveLostItemsMenuUpdates(): void
    {
        $menu = $this->getMenu();

        $menuUpdate = new MenuUpdateStub(42);
        $menuUpdate2 = new MenuUpdateStub(142);
        $context = (new MenuUpdateApplierContext($menu))
            ->addLostItem($menu->getChild('item-1'), $menuUpdate)
            ->addLostItem($menu->getChild('item-2'), $menuUpdate2);

        self::assertEquals(
            ['item-1' => $menu->getChild('item-1'), 'item-2' => $menu->getChild('item-2')],
            $context->getLostItems()
        );

        $context->removeLostItem('item-1');

        self::assertEquals(
            ['item-2' => $menu->getChild('item-2')],
            $context->getLostItems()
        );

        self::assertEquals(
            ['item-2' => [$menuUpdate2->getId() => $menuUpdate2]],
            $context->getLostItemsMenuUpdates()
        );
    }

    public function testIsLostItem(): void
    {
        $menu = $this->getMenu();

        $context = (new MenuUpdateApplierContext($menu))
            ->addCreatedItem($menu->getChild('item-1'), $this->createMock(MenuUpdateInterface::class))
            ->addLostItem($menu->getChild('item-2'), $this->createMock(MenuUpdateInterface::class));

        self::assertFalse($context->isLostItem('item-1'));
        self::assertTrue($context->isLostItem('item-2'));
    }
}
