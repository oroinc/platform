<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\MenuContentProvider;

class MenuContentProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $menuExtension;

    /**
     * @var string
     */
    protected $menu;

    /**
     * @var MenuContentProvider
     */
    protected $provider;

    protected function setUp(): void
    {
        $this->menuExtension = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Twig\MenuExtension')
            ->disableOriginalConstructor()
            ->getMock();
        $this->menu = 'test';

        $this->provider = new MenuContentProvider($this->menuExtension, $this->menu);
    }

    public function testGetContent()
    {
        $this->menuExtension->expects($this->once())
            ->method('render')
            ->with($this->menu)
            ->will($this->returnValue('rendered_menu'));
        $this->assertEquals('rendered_menu', $this->provider->getContent());
    }
}
