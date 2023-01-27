<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\MenuUpdate\Propagator\ToMenuUpdate;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\BasicPropagator;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;

class BasicPropagatorTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    private BasicPropagator $propagator;

    protected function setUp(): void
    {
        $this->propagator = new BasicPropagator();
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(string $strategy, bool $expected): void
    {
        $menuUpdate = new MenuUpdateStub();
        $menu = $this->createMock(ItemInterface::class);

        self::assertSame(
            $expected,
            $this->propagator->isApplicable($menuUpdate, $menu, $strategy)
        );
    }

    public function isApplicableDataProvider(): array
    {
        return [
            'none' => [
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_NONE,
                'expected' => false,
            ],
            'basic' => [
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC,
                'expected' => true,
            ],
            'full' => [
                'strategy' => MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL,
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider propagateFromMenuItemDataProvider
     */
    public function testPropagateFromMenuItem(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menuItem,
        MenuUpdateInterface $expected
    ): void {
        $this->propagator->propagateFromMenuItem(
            $menuUpdate,
            $menuItem,
            MenuItemToMenuUpdatePropagatorInterface::STRATEGY_BASIC
        );

        self::assertEquals($expected, $menuUpdate);
    }

    public function propagateFromMenuItemDataProvider(): array
    {
        $menu = $this->createItem('sample_menu');
        $menu
            ->addChild('menu_item_not_displayed')
            ->setDisplay(false);
        $menu
            ->addChild('menu_item_has_child')
            ->addChild('menu_item_has_parent');
        $menu
            ->addChild('menu_item_with_uri')
            ->setUri('http://example.org');
        $menu
            ->addChild('menu_item_for_existing_menu_update')
            ->setDisplay(false)
            ->setUri('new/uri')
            ->setParent($menu->getChild('menu_item_has_child'));

        return [
            'without parent' => [
                'menuUpdate' => (new MenuUpdateStub())->setKey($menu->getName()),
                'menuItem' => $menu,
                'expected' => (new MenuUpdateStub())
                    ->setKey($menu->getName()),
            ],
            'not displayed' => [
                'menuUpdate' => (new MenuUpdateStub())->setKey('menu_item_not_displayed'),
                'menuItem' => $menu->getChild('menu_item_not_displayed'),
                'expected' => (new MenuUpdateStub())->setKey('menu_item_not_displayed')->setActive(false),
            ],
            'with parent, menu update has no parentKey' => [
                'menuUpdate' => (new MenuUpdateStub())->setKey('menu_item_has_parent'),
                'menuItem' => $menu->getChild('menu_item_has_child')->getChild('menu_item_has_parent'),
                'expected' => (new MenuUpdateStub())
                    ->setKey('menu_item_has_parent')
                    ->setParentKey('menu_item_has_child'),
            ],
            'with parent, menu update has parentKey' => [
                'menuUpdate' => (new MenuUpdateStub())
                    ->setKey('menu_item_has_parent')
                    ->setParentKey('already_set_parent'),
                'menuItem' => $menu->getChild('menu_item_has_child')->getChild('menu_item_has_parent'),
                'expected' => (new MenuUpdateStub())
                    ->setKey('menu_item_has_parent')
                    ->setParentKey('menu_item_has_child'),
            ],
            'with uri' => [
                'menuUpdate' => (new MenuUpdateStub())->setKey('menu_item_with_uri'),
                'menuItem' => $menu->getChild('menu_item_with_uri'),
                'expected' => (new MenuUpdateStub())
                    ->setKey('menu_item_with_uri')
                    ->setUri('http://example.org'),
            ],
            'menu update is not new' => [
                'menuUpdate' => (new MenuUpdateStub(42))
                    ->setKey('menu_item_has_parent')
                    ->setParentKey('sample_parent_key')
                    ->setCustom(true)
                    ->setUri('existing/uri')
                    ->setActive(true),
                'menuItem' => $menu->getChild('menu_item_for_existing_menu_update'),
                'expected' => (new MenuUpdateStub(42))
                    ->setKey('menu_item_has_parent')
                    ->setParentKey('menu_item_has_child')
                    ->setCustom(true)
                    ->setUri('existing/uri')
                    ->setActive(true),
            ],
        ];
    }
}
