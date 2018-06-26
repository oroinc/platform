<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\EventListener;

use Oro\Bundle\DashboardBundle\EventListener\NavigationListener;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class NavigationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var NavigationListener */
    protected $navigationListener;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
    protected $manager;

    protected function setUp()
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->manager = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\Manager')
            ->setMethods(['getDashboards', 'findAllowedDashboardsShortenedInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->navigationListener = new NavigationListener(
            $this->tokenAccessor,
            $this->manager
        );
    }

    public function testonnavigationconfigureWithoutUser()
    {
        /** @var ConfigureMenuEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenAccessor->expects($this->atLeastOnce())->method('hasUser')->willReturn(false);
        $this->manager->expects($this->never())->method('findAllowedDashboardsShortenedInfo');

        $this->navigationListener->onNavigationConfigure($event);
    }

    public function testOnNavigationConfigureWithDisabledDisplaying()
    {
        /** @var ConfigureMenuEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenAccessor->expects($this->once())->method('hasUser')->willReturn(true);

        $menu = $this->createMock('Knp\Menu\ItemInterface');
        $item = $this->createMock('Knp\Menu\ItemInterface');
        $item->expects($this->once())->method('isDisplayed')->willReturn(false);

        $event->expects($this->once())->method('getMenu')->will($this->returnValue($menu));

        $this->manager->expects($this->never())->method('findAllowedDashboardsShortenedInfo');

        $menu->expects($this->once())->method('getChild')->with('dashboard_tab')->willReturn($item);

        $this->navigationListener->onNavigationConfigure($event);
    }

    public function testOnNavigationConfigureAddCorrectItems()
    {
        $event = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $id = 42;
        $secondId = 43;
        $expectedLabel = 'expected label';
        $secondExpectedLabel = 'test expected label';

        $dashboards = [
            ['id' => $id, 'label' => $expectedLabel],
            ['id' => $secondId, 'label' => $secondExpectedLabel],
        ];
        $menuItemAlias = $id.'_dashboard_menu_item';
        $secondMenuItemAlias = $secondId.'_dashboard_menu_item';

        $expectedOptions = array(
            'label'           => $expectedLabel,
            'route'           => 'oro_dashboard_view',
            'extras'          => array(
                'position' => 1
            ),
            'routeParameters' => array(
                'id'               => $id,
                'change_dashboard' => true
            )
        );
        $secondExpectedOptions = array(
            'label'           => $secondExpectedLabel,
            'route'           => 'oro_dashboard_view',
            'extras'          => array(
                'position' => 1
            ),
            'routeParameters' => array(
                'id'               => $secondId,
                'change_dashboard' => true
            )
        );

        $menu = $this->createMock('Knp\Menu\ItemInterface');
        $item = $this->createMock('Knp\Menu\ItemInterface');
        $child = $this->createMock('Knp\Menu\ItemInterface');
        $child->expects($this->atLeastOnce())->method('setAttribute')->with('data-menu')->will($this->returnSelf());

        $divider = $this->createMock('Knp\Menu\ItemInterface');
        $divider->expects($this->once())->method('setLabel')->with('')->will($this->returnSelf());
        $divider->expects($this->once())->method('setAttribute')->with('class', 'menu-divider')
            ->will($this->returnSelf());
        $divider->expects($this->exactly(2))->method('setExtra')
            ->will($this->returnValueMap([
                ['position', 2, $divider],
                ['divider', true, $divider]
            ]));

        $item->expects($this->at(1))
            ->method('addChild')
            ->with($menuItemAlias, $this->equalTo($expectedOptions))
            ->will($this->returnValue($child));
        $item->expects($this->at(2))
            ->method('addChild')
            ->with($secondMenuItemAlias, $this->equalTo($secondExpectedOptions))
            ->will($this->returnValue($child));
        $item->expects($this->at(3))->method('addChild')->will($this->returnValue($divider));

        $menu->expects($this->once())->method('getChild')->will($this->returnValue($item));
        $item->expects($this->once())->method('isDisplayed')->willReturn(true);
        $event->expects($this->once())->method('getMenu')->will($this->returnValue($menu));
        $this->tokenAccessor->expects($this->once())->method('hasUser')->will($this->returnValue(true));
        $this->tokenAccessor->expects($this->once())->method('getOrganizationId')->willReturn(null);
        $this->manager->expects($this->once())->method('findAllowedDashboardsShortenedInfo')
            ->willReturn($dashboards);

        $this->navigationListener->onNavigationConfigure($event);
    }
}
