<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;

trait MenuItemTestTrait
{
    /**
     * @return ItemInterface
     */
    public function getMenu()
    {
        $factory = new MenuFactory();

        $menu = $factory->createItem('menu');

        $item1 = $factory->createItem('item-1');
        $item2 = $factory->createItem('item-2');
        $item3 = $factory->createItem('item-3');
        $item4 = $factory->createItem('item-4');

        $item11 = $factory->createItem('item-1-1');
        $item12 = $factory->createItem('item-1-2');

        $item111 = $factory->createItem('item-1-1-1');

        $menu->addChild($item1);
        $menu->addChild($item2);
        $menu->addChild($item3);
        $menu->addChild($item4);

        $item1->addChild($item11);
        $item1->addChild($item12);

        $item11->addChild($item111);

        return $menu;
    }

    /**
     * @param string $name
     *
     * @return ItemInterface
     */
    public function createItem($name)
    {
        $factory = new MenuFactory();

        return $factory->createItem($name);
    }
}
