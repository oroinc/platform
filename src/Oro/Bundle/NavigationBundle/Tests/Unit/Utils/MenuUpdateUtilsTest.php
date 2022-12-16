<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Utils;

use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Utils\LostItemsManipulator;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\ReflectionUtil;

class MenuUpdateUtilsTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private MenuUpdateHelper|\PHPUnit\Framework\MockObject\MockObject $menuUpdateHelper;

    protected function setUp(): void
    {
        $this->menuUpdateHelper = $this->createMock(MenuUpdateHelper::class);
    }

    public function testFindMenuItem(): void
    {
        $menu = $this->getMenu();

        $expectedItem = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        self::assertEquals($expectedItem, MenuUpdateUtils::findMenuItem($menu, 'item-1-1-1'));
    }

    public function testFindMenuItemNull(): void
    {
        $menu = $this->getMenu();

        self::assertEquals(null, MenuUpdateUtils::findMenuItem($menu, 'item-1-1-1-1'));
    }

    public function testGenerateKey(): void
    {
        $menuName = 'application_menu';

        $scope = new Scope();
        ReflectionUtil::setId($scope, 1);

        self::assertEquals('application_menu_1', MenuUpdateUtils::generateKey($menuName, $scope));
    }

    public function testFlattenMenuItemWhenNoChildren(): void
    {
        $menuItem = $this->createItem('sample_menu');
        self::assertEquals([$menuItem->getName() => $menuItem], MenuUpdateUtils::flattenMenuItem($menuItem));
    }

    public function testFlattenMenuItemWhenHasChildren(): void
    {
        $menu = $this->getMenu();

        self::assertEquals(
            [
                $menu->getName() => $menu,
                'item-1' => $menu->getChild('item-1'),
                'item-2' => $menu->getChild('item-2'),
                'item-3' => $menu->getChild('item-3'),
                'item-4' => $menu->getChild('item-4'),
                'item-1-1' => $menu->getChild('item-1')->getChild('item-1-1'),
                'item-1-1-1' => $menu->getChild('item-1')->getChild('item-1-1')->getChild('item-1-1-1'),
                'item-1-2' => $menu->getChild('item-1')->getChild('item-1-2'),
            ],
            MenuUpdateUtils::flattenMenuItem($menu),
        );
    }

    public function testGetAllowedNestingLevelWhenNoParent(): void
    {
        $menu = $this->createItem('sample_menu');
        self::assertEquals(0, MenuUpdateUtils::getAllowedNestingLevel($menu));
    }

    /**
     * @dataProvider getAllowedNestingLevelWhenHasParentDataProvider
     */
    public function testGetAllowedNestingLevelWhenHasParent(int $maxNestingLevel, int $expected): void
    {
        $menu = $this->createItem('sample_menu')
            ->setExtra(ConfigurationBuilder::MAX_NESTING_LEVEL, $maxNestingLevel);
        $item1 = $menu->addChild('menu_item1');
        $item11 = $this->createItem('menu_item11');
        $item1->addChild($item11);

        self::assertEquals($expected, MenuUpdateUtils::getAllowedNestingLevel($item11));
    }

    public function getAllowedNestingLevelWhenHasParentDataProvider(): array
    {
        return [
            ['maxNestingLevel' => 0, 'expected' => 0],
            ['maxNestingLevel' => 2, 'expected' => 0],
            ['maxNestingLevel' => 5, 'expected' => 3],
        ];
    }

    /**
     * @dataProvider getAllowedNestingLevelWhenHasImpliedParentDataProvider
     */
    public function testGetAllowedNestingLevelWhenHasImpliedParent(int $maxNestingLevel, int $expected): void
    {
        $menu = $this->createItem('sample_menu')
            ->setExtra(ConfigurationBuilder::MAX_NESTING_LEVEL, $maxNestingLevel);
        $item1 = $menu->addChild('menu_item1');
        $item11 = $this->createItem('menu_item11');
        $item1->addChild($item11);

        $item111 = $this->createItem('menu_item111')
            ->setExtra(LostItemsManipulator::IMPLIED_PARENT_NAME, $item11->getName());

        $lostItemsContainer = LostItemsManipulator::getLostItemsContainer($menu);
        $lostItemsContainer->addChild($item111);

        self::assertEquals($expected, MenuUpdateUtils::getAllowedNestingLevel($item111));
    }

    public function getAllowedNestingLevelWhenHasImpliedParentDataProvider(): array
    {
        return [
            ['maxNestingLevel' => 0, 'expected' => 0],
            ['maxNestingLevel' => 3, 'expected' => 0],
            ['maxNestingLevel' => 5, 'expected' => 2],
        ];
    }
}
