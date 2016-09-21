<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Builder;

use Knp\Menu\MenuFactory;

use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProviderInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;

class MenuUpdateBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var MenuUpdateBuilder */
    protected $builder;

    /** @var MenuUpdateHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $menuUpdateHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->menuUpdateHelper = $this->getMock(MenuUpdateHelper::class, [], [], '', false);

        $this->builder = new MenuUpdateBuilder($this->menuUpdateHelper);
    }

    public function testBuild()
    {
        $menuUpdate = new MenuUpdateStub();
        $menuUpdate->setMenu('menu');

        /** @var MenuUpdateProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock(MenuUpdateProviderInterface::class);
        $provider->expects($this->once())
            ->method('getUpdates')
            ->will($this->returnValue([$menuUpdate]));

        $this->builder->addProvider('default', $provider);

        $factory = new MenuFactory();
        $menu = $factory->createItem('menu');
        $menu->setExtra('area', 'default');

        $this->menuUpdateHelper
            ->expects($this->once())
            ->method('updateMenuItem')
            ->with($menuUpdate, $menu);

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

        $factory = new MenuFactory();
        $menu = $factory->createItem('menu');

        $menu->setExtra('area', 'custom');

        $this->builder->build($menu);
    }
}
