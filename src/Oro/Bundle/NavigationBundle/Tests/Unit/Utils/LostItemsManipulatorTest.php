<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Utils;

use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Utils\LostItemsManipulator;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LostItemsManipulatorTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private MenuUpdateHelper|\PHPUnit\Framework\MockObject\MockObject $menuUpdateHelper;

    protected function setUp(): void
    {
        $this->menuUpdateHelper = $this->createMock(MenuUpdateHelper::class);
    }

    public function testGetLostItemsContainer(): void
    {
        $menu = $this->createItem('sample_menu');

        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu);
        self::assertNotNull($lostItemsContainer);
        self::assertEquals(LostItemsManipulator::LOST_ITEMS_CONTAINER, $lostItemsContainer->getName());

        self::assertSame($lostItemsContainer, LostItemsManipulator::getLostItemsContainer($menu));
        self::assertSame($lostItemsContainer, LostItemsManipulator::getLostItemsContainer($menu, false));
    }

    public function testGetLostItemsContainerWhenNoRoot(): void
    {
        $menu = $this->createItem('sample_menu');
        $item1 = $menu->addChild('menu_item1');

        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($item1);
        self::assertNotNull($lostItemsContainer);
        self::assertEquals(LostItemsManipulator::LOST_ITEMS_CONTAINER, $lostItemsContainer->getName());

        self::assertSame($lostItemsContainer, LostItemsManipulator::getLostItemsContainer($item1));
        self::assertSame($lostItemsContainer, LostItemsManipulator::getLostItemsContainer($item1, false));
    }

    public function testGetLostItemsContainerWhenNotCreate(): void
    {
        $menu = $this->createItem('sample_menu');

        self::assertNull(LostItemsManipulator::getLostItemsContainer($menu, false));
    }

    public function testIsLostItemContainer(): void
    {
        $menu = $this->createItem('sample_menu');

        self::assertFalse(LostItemsManipulator::isLostItemsContainer($menu));

        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu);
        self::assertTrue(LostItemsManipulator::isLostItemsContainer($lostItemsContainer));
    }

    public function testGetParentWhenNoParent(): void
    {
        $menu = $this->createItem('sample_menu');
        self::assertNull(LostItemsManipulator::getParent($menu));
    }

    public function testGetParentWhenHasParent(): void
    {
        $menu = $this->createItem('sample_menu');
        $item1 = $menu->addChild('menu_item1');
        $item11 = $this->createItem('menu_item11');
        $item1->addChild($item11);

        self::assertSame($item1, LostItemsManipulator::getParent($item11));
    }

    public function testGetParentWhenHasImpliedParent(): void
    {
        $menu = $this->createItem('sample_menu');
        $item1 = $menu->addChild('menu_item1');
        $item11 = $this->createItem('menu_item11')
            ->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $item1->getName());
        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu);
        $lostItemsContainer->addChild($item11);

        self::assertSame($item1, LostItemsManipulator::getParent($item11));
    }

    public function testGetParentsWhenNoParent(): void
    {
        $menu = $this->createItem('sample_menu');
        self::assertSame([], LostItemsManipulator::getParents($menu));
    }

    public function testGetParentsWhenHasParent(): void
    {
        $menu = $this->createItem('sample_menu');
        $item1 = $menu->addChild('menu_item1');
        $item11 = $this->createItem('menu_item11');
        $item1->addChild($item11);

        self::assertSame(
            [
                $menu->getName() => $menu,
                $item1->getName() => $item1,
            ],
            LostItemsManipulator::getParents($item11)
        );
    }

    public function testGetParentsWhenHasImpliedParent(): void
    {
        $menu = $this->createItem('sample_menu');
        $item1 = $menu->addChild('menu_item1');
        $item11 = $this->createItem('menu_item11');
        $item1->addChild($item11);

        $item111 = $this->createItem('menu_item111')
            ->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $item11->getName());

        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu);
        $lostItemsContainer->addChild($item111);

        self::assertSame(
            [
                $menu->getName() => $menu,
                $item1->getName() => $item1,
                $item11->getName() => $item11,
            ],
            LostItemsManipulator::getParents($item111)
        );
    }
}
