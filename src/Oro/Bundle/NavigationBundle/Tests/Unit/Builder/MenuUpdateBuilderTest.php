<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Builder;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Entity\AbstractMenuUpdate;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;

class MenuUpdateBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var MenuUpdateBuilder */
    protected $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->builder = new MenuUpdateBuilder();
    }

    public function testBuild()
    {
        /** @var AbstractMenuUpdate|\PHPUnit_Framework_MockObject_MockObject $update */
        $update = $this->getMock(AbstractMenuUpdate::class);
        $update->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue('menu_key'));
        $update->expects($this->any())
            ->method('getKey')
            ->will($this->returnValue('key'));
        $update->expects($this->any())
            ->method('getParentKey')
            ->will($this->returnValue(null));
        $update->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue('title'));
        $update->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue('#'));
        $update->expects($this->once())
            ->method('isActive')
            ->will($this->returnValue(true));
        $update->expects($this->once())
            ->method('getExtras')
            ->will($this->returnValue(['extra_key' => 'extra_value']));
        $update->expects($this->any())
            ->method('getPriority')
            ->will($this->returnValue(10));

        /** @var MenuUpdateProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(MenuUpdateProviderInterface::class);
        $provider->expects($this->once())
            ->method('getUpdates')
            ->will($this->returnValue([$update]));

        $this->builder->addProvider('default', $provider);

        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $resultItem */
        $resultItem = $this->getMock(ItemInterface::class);
        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $nestedItem */
        $nestedItem = $this->getMock(ItemInterface::class);
        $nestedItem->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('nested_name'));

        $resultItem->expects($this->any())
            ->method('getParent')
            ->will($this->returnValue($nestedItem));
        $resultItem->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('key'));
        $resultItem->expects($this->once())
            ->method('setLabel')
            ->with('title');
        $resultItem->expects($this->once())
            ->method('setUri')
            ->with('#');
        $resultItem->expects($this->once())
            ->method('setDisplay')
            ->with(true);
        $resultItem->expects($this->any())
            ->method('setExtra');

        $nestedItem->expects($this->once())
            ->method('getChild')
            ->with('key')
            ->will($this->returnValue($resultItem));
        $nestedItem->expects($this->once())
            ->method('removeChild')
            ->with('key');

        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $menu */
        $menu = $this->getMock(ItemInterface::class);
        $menu->expects($this->at(0))
            ->method('getExtra')
            ->with('area')
            ->will($this->returnValue('default'));
        $menu->expects($this->at(1))
            ->method('getName')
            ->will($this->returnValue('menu_key'));
        $menu->expects($this->at(2))
            ->method('getChild')
            ->with('key')
            ->will($this->returnValue(null));
        $menu->expects($this->at(3))
            ->method('getChildren')
            ->will($this->returnValue([$nestedItem]));
        $menu->expects($this->at(4))
            ->method('getChild')
            ->with(null)
            ->will($this->returnValue(null));
        $menu->expects($this->at(5))
            ->method('getChildren')
            ->will($this->returnValue([]));
        $menu->expects($this->at(6))
            ->method('getName')
            ->will($this->returnValue('menu_key'));
        $menu->expects($this->at(7))
            ->method('addChild')
            ->with($resultItem)
            ->will($this->returnValue($resultItem));

        $this->builder->build($menu);
    }

    /**
     * @expectedException \Oro\Bundle\NavigationBundle\Exception\ProviderNotFoundException
     * @expectedExceptionMessage Provider related to "custom" area not found.
     */
    public function testBuildProviderNotFoundException()
    {
        /** @var MenuUpdateProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(MenuUpdateProviderInterface::class);

        $this->builder->addProvider('default', $provider);

        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject $menu */
        $menu = $this->getMock(ItemInterface::class);
        $menu->expects($this->once())
            ->method('getExtra')
            ->with('area')
            ->will($this->returnValue('custom'));

        $this->builder->build($menu);
    }
}
