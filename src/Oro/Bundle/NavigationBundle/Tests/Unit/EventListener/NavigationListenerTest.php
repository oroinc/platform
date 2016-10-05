<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\EventListener\NavigationListener;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class NavigationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NavigationListener
     */
    protected $navigationListener;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->navigationListener = new NavigationListener($this->securityFacade);
    }

    public function testDisplayIsSet()
    {
        $item = $this->getMock('Knp\Menu\ItemInterface');
        $menu = $this->getMock('Knp\Menu\ItemInterface');

        /** @var ConfigureMenuEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('\Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())->method('getMenu')->willReturn($menu);
        $menu->expects($this->once())->method('getChild')->willReturn($item);
        $item->expects($this->once())->method('setDisplay')->with(false);
        $this->securityFacade->expects($this->once())->method('isGranted')->willReturn(false);

        $this->navigationListener->onNavigationConfigure($event);
    }

    /**
     * @dataProvider displayIsNotSetProvider
     *
     * @param $item
     * @param $isGranted
     */
    public function testDisplayIsNotSet($item, $isGranted)
    {
        $menu = $this->getMock('Knp\Menu\ItemInterface');

        /** @var ConfigureMenuEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('\Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())->method('getMenu')->willReturn($menu);
        $menu->expects($this->once())->method('getChild')->willReturn($item);
        $menu->expects($this->any())->method('getChildren')->willReturn([]);
        $this->securityFacade->expects($this->any())->method('isGranted')->willReturn($isGranted);

        $this->navigationListener->onNavigationConfigure($event);
    }

    public function displayIsNotSetProvider()
    {
        return [
            'with empty item' => [
                'item' => null,
                'isGranted' => true,
            ],
            'with no access rights' => [
                'item' => $this->getMock('Knp\Menu\ItemInterface'),
                'isGranted' => false,
            ],
        ];
    }
}
