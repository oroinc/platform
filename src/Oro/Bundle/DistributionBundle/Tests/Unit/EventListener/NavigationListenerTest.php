<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\EventListener\NavigationListener;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;

class NavigationListenerTest extends \PHPUnit_Framework_TestCase
{
    use MockHelperTrait;

    const ENTRY_POINT = 'install.php';

    /**
     * @test
     */
    public function shouldBeConstructedWithSecurityFacade()
    {
        new NavigationListener($this->createSecurityContextMock());
    }

    /**
     * @test
     */
    public function couldBeConstructedWithEntryPoint()
    {
        new NavigationListener($this->createSecurityContextMock(), self::ENTRY_POINT);
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfEntryPointWasNotDefined()
    {
        $security = $this->createSecurityContextMock();
        $listener = new NavigationListener($security);

        $security->expects($this->never())
            ->method('getToken');
        $security->expects($this->never())
            ->method('isGranted');
        $listener->onNavigationConfigure($this->createEventMock());
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfNoLoggedInUser()
    {
        $security = $this->createSecurityContextMock();

        $security->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null));
        $security->expects($this->never())
            ->method('isGranted');

        $listener = new NavigationListener($security, self::ENTRY_POINT);
        $listener->onNavigationConfigure($this->createEventMock());
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfNotUserDoesNotHaveRoleAdministrator()
    {
        $security = $this->createSecurityContextMock();

        $security->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(true));
        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMINISTRATOR')
            ->will($this->returnValue(false));

        $listener = new NavigationListener($security, self::ENTRY_POINT);
        $listener->onNavigationConfigure($this->createEventMock());
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfMenuDoesNotHaveSystemTab()
    {
        $security = $this->createSecurityContextMock();
        $listener = new NavigationListener($security, self::ENTRY_POINT);

        $security->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(true));
        $security->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $event = $this->createEventMock();

        $menu = $this->createMenuItemMock();
        $event->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue($menu));

        $menu->expects($this->once())
            ->method('getChild')
            ->with('system_tab')
            ->will($this->returnValue(false));

        $listener->onNavigationConfigure($event);
    }

    /**
     * @test
     */
    public function shouldAddMenuItem()
    {
        $security = $this->createSecurityContextMock();
        $listener = new NavigationListener($security, self::ENTRY_POINT);

        $security->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(true));
        $security->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $event = $this->createEventMock();

        $menu = $this->createMenuItemMock();
        $event->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue($menu));

        $systemTab = $this->createMenuItemMock();
        $menu->expects($this->once())
            ->method('getChild')
            ->with('system_tab')
            ->will($this->returnValue($systemTab));

        $systemTab->expects($this->once())
            ->method('addChild')
            ->with(
                'package_manager',
                [
                    'label'          => 'oro.distribution.package_manager.label',
                    'uri'            => '/' . self::ENTRY_POINT,
                    'linkAttributes' => ['class' => 'no-hash'],
                    'extras'         => ['position' => '110']
                ]
            )
            ->will($this->returnValue($systemTab));

        $listener->onNavigationConfigure($event);
    }

    /**
     * @test
     */
    public function shouldAddMenuItemForSubdirectory()
    {
        $security = $this->createSecurityContextMock();

        $listener = new NavigationListener($security, self::ENTRY_POINT);

        $security
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(true));
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $request = $this->createRequestMock();
        $request
            ->expects($this->once())
            ->method('getBasePath')
            ->will($this->returnValue('/subdir'));

        $listener->setRequest($request);

        $event = $this->createEventMock();

        $menu = $this->createMenuItemMock();
        $event
            ->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue($menu));

        $systemTab = $this->createMenuItemMock();
        $menu
            ->expects($this->once())
            ->method('getChild')
            ->with('system_tab')
            ->will($this->returnValue($systemTab));

        $systemTab
            ->expects($this->once())
            ->method('addChild')
            ->with(
                'package_manager',
                [
                    'label'          => 'oro.distribution.package_manager.label',
                    'uri'            => '/subdir/' . self::ENTRY_POINT,
                    'linkAttributes' => ['class' => 'no-hash'],
                    'extras'         => ['position' => '110']
                ]
            )
            ->will($this->returnValue($systemTab));

        $listener->onNavigationConfigure($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMenuItemMock()
    {
        return $this->createConstructorLessMock('Knp\Menu\ItemInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEventMock()
    {
        return $this->createConstructorLessMock('Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createSecurityContextMock()
    {
        return $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createRequestMock()
    {
        return $this->createConstructorLessMock('Symfony\Component\HttpFoundation\Request');
    }
}
