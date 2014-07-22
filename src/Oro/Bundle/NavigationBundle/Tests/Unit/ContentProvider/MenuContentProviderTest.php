<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\MenuContentProvider;

class MenuContentProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $menuExtension;

    /**
     * @var string
     */
    protected $menu;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var MenuContentProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->menuExtension = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Twig\MenuExtension')
            ->disableOriginalConstructor()
            ->getMock();
        $this->menu = 'test';
        $this->name = 'test_menu';

        $this->provider = new MenuContentProvider($this->menuExtension, $this->menu, $this->name);
    }

    public function testGetContent()
    {
        $this->menuExtension->expects($this->once())
            ->method('render')
            ->with($this->menu)
            ->will($this->returnValue('rendered_menu'));
        $this->assertEquals('rendered_menu', $this->provider->getContent());
    }

    public function testGetName()
    {
        $this->assertEquals($this->name, $this->provider->getName());
    }
}
