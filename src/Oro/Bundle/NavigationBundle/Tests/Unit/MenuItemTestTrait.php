<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;

trait MenuItemTestTrait
{
    /**
     * @var MenuItem
     */
    protected $menu;

    /**
     * @var MenuItem
     */
    protected $pt1;

    /**
     * @var MenuItem
     */
    protected $pt2;

    /**
     * @var MenuItem
     */
    protected $ch1;

    public function prepareMenu()
    {
        $factory = new MenuFactory();
        
        $this->menu = $factory->createItem('Root Menu');
        
        $this->pt1 = $this->menu->addChild('Parent 1');
        $this->pt2 = $this->menu->addChild('Parent 2');
        
        $this->ch1 = $this->pt1->addChild('Child 1');
    }
}
