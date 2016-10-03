<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Oro\Bundle\NavigationBundle\EventListener\NavigationListener;

class NavigationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NavigationListener
     */
    protected $navigationListener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $menuUpdateManager;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->menuUpdateManager = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->navigationListener = new NavigationListener($this->securityFacade, $this->menuUpdateManager);
    }

    public function testDisplayIsSet()
    {
        $menu = $this->getMock('Knp\Menu\ItemInterface');
        $item = $this->getMock('Knp\Menu\ItemInterface');
        $event = $this->getMockBuilder('\Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getMenu')->willReturn($menu);

        $item->expects($this->once())->method('setDisplay')->with(false);

        $this->securityFacade->expects($this->once())->method('isGranted')->willReturn(false);
        $this->menuUpdateManager->expects($this->once())->method('findMenuItem')->willReturn($item);

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
        $event = $this->getMockBuilder('\Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getMenu')->willReturn($menu);

        $this->securityFacade->expects($this->any())->method('isGranted')->willReturn($isGranted);
        $this->menuUpdateManager->expects($this->once())->method('findMenuItem')->willReturn($item);

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