<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Utils;

use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
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
}
