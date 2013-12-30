<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\EventListener;


use Oro\Bundle\DistributionBundle\EventListener\NavigationListener;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;

class NavigationListenerTest extends \PHPUnit_Framework_TestCase
{
    use MockHelperTrait;

    /**
     * @test
     */
    public function shouldBeConstructedWithSecurityFacade()
    {
        new NavigationListener($this->createSecurityFacadeMock());
    }

    /**
     * @test
     */
    public function couldBeConstructedWithEntryPoint()
    {
        new NavigationListener(
            $this->createSecurityFacadeMock(), $entryPoint = '/install.php'
        );
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfEntryPointWasNotDefined()
    {
        $listener = new NavigationListener($this->createSecurityFacadeMock());
        $listener->onNavigationConfigure($this->createEventMock());
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfNotUserDoesNotHaveRoleAdministrator()
    {
        $security = $this->createSecurityFacadeMock();
        $listener = new NavigationListener($security, '/install.php');

        $security->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMINISTRATOR')
            ->will($this->returnValue(false));

        $listener->onNavigationConfigure($this->createEventMock());
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfMenuDoesNotHaveSystemTab()
    {
        $security = $this->createSecurityFacadeMock();
        $listener = new NavigationListener($security, '/install.php');

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
        $security = $this->createSecurityFacadeMock();
        $listener = new NavigationListener($security, $entryPoint = '/install.php');

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
                    'label' => 'Package Manager',
                    'uri' => $entryPoint,
                    'linkAttributes' => ['class' => 'no-hash']
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
    protected function createSecurityFacadeMock()
    {
        return $this->createConstructorLessMock('Oro\Bundle\SecurityBundle\SecurityFacade');
    }
}
