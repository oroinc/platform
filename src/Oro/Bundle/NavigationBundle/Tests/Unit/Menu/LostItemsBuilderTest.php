<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Oro\Bundle\NavigationBundle\Menu\LostItemsBuilder;
use Oro\Bundle\NavigationBundle\MenuUpdateApplier\MenuUpdateApplier;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Utils\LostItemsManipulator;

class LostItemsBuilderTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private LostItemsBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new LostItemsBuilder();
    }

    public function testWhenNotDisplayed(): void
    {
        $menu = $this->createItem('sample_menu')
            ->setDisplay(false);
        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu);

        $this->builder->build($menu);

        self::assertSame($lostItemsContainer, $menu->getChild($lostItemsContainer->getName()));
    }

    public function testWhenNoLostItemsContainer(): void
    {
        $menu = $this->createItem('sample_menu');
        self::assertNull(LostItemsManipulator::getLostItemsContainer($menu, false));

        $this->builder->build($menu);

        self::assertNull(LostItemsManipulator::getLostItemsContainer($menu, false));
    }

    public function testWhenLostItemsContainerIsEmpty(): void
    {
        $menu = $this->createItem('sample_menu');
        LostItemsManipulator::getLostItemsContainer($menu);

        $this->builder->build($menu);

        self::assertNull(LostItemsManipulator::getLostItemsContainer($menu, false));
    }

    public function testWhenLostItemsContainerHasNonCustomItem(): void
    {
        $menu = $this->createItem('sample_menu');
        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu);
        $nonCustomItem = $lostItemsContainer->addChild('non_custom');

        $this->builder->build($menu);

        self::assertSame([$nonCustomItem->getName() => $nonCustomItem], $lostItemsContainer->getChildren());
        self::assertSame([], $menu->getChildren());
    }

    public function testWhenLostItemsContainerHasCustomItem(): void
    {
        $menu = $this->createItem('sample_menu');
        $menuItem1 = $this->createItem('item1');
        $menu->addChild($menuItem1);
        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu);
        $customItem = $lostItemsContainer->addChild('custom_item')
            ->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $menuItem1->getName())
            ->setExtra(MenuUpdateApplier::IS_CUSTOM, true);

        $this->builder->build($menu);

        self::assertSame([], $lostItemsContainer->getChildren());
        self::assertSame([$menuItem1->getName() => $menuItem1], $menu->getChildren());
        self::assertSame([$customItem->getName() => $customItem], $menuItem1->getChildren());
    }

    public function testWhenLostItemsContainerHasCustomItemButNoParent(): void
    {
        $menu = $this->createItem('sample_menu');
        $menuItem1 = $this->createItem('item1');
        $menu->addChild($menuItem1);
        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu);
        $customItem = $lostItemsContainer->addChild('custom_item')
            ->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, 'non_existing_parent')
            ->setExtra(MenuUpdateApplier::IS_CUSTOM, true);

        $this->builder->build($menu);

        self::assertSame([], $lostItemsContainer->getChildren());
        self::assertSame(
            [
                $menuItem1->getName() => $menuItem1,
                $customItem->getName() => $customItem,
            ],
            $menu->getChildren()
        );
        self::assertSame([], $menuItem1->getChildren());
    }
}
