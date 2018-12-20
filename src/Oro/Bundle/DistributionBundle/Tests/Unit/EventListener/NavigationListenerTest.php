<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\EventListener\NavigationListener;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;

class NavigationListenerTest extends \PHPUnit\Framework\TestCase
{
    use MockHelperTrait;

    const ENTRY_POINT = 'install.php';

    /**
     * @test
     */
    public function shouldBeConstructedWithSecurityContext()
    {
        new NavigationListener($this->createAuthorizationCheckerMock(), $this->createTokenStorageMock());
    }

    /**
     * @test
     */
    public function couldBeConstructedWithEntryPoint()
    {
        new NavigationListener(
            $this->createAuthorizationCheckerMock(),
            $this->createTokenStorageMock(),
            self::ENTRY_POINT
        );
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfEntryPointWasNotDefined()
    {
        $authorizationChecker = $this->createAuthorizationCheckerMock();
        $tokenStorage = $this->createTokenStorageMock();

        $tokenStorage->expects($this->never())
            ->method('getToken');
        $authorizationChecker->expects($this->never())
            ->method('isGranted');

        $listener = new NavigationListener($authorizationChecker, $tokenStorage);
        $listener->onNavigationConfigure($this->createEventMock());
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfNoLoggedInUser()
    {
        $authorizationChecker = $this->createAuthorizationCheckerMock();
        $tokenStorage = $this->createTokenStorageMock();

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null));
        $authorizationChecker->expects($this->never())
            ->method('isGranted');

        $listener = new NavigationListener($authorizationChecker, $tokenStorage, self::ENTRY_POINT);
        $listener->onNavigationConfigure($this->createEventMock());
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfNotUserDoesNotHaveRoleAdministrator()
    {
        $authorizationChecker = $this->createAuthorizationCheckerMock();
        $tokenStorage = $this->createTokenStorageMock();

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(true));
        $authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMINISTRATOR')
            ->will($this->returnValue(false));

        $listener = new NavigationListener($authorizationChecker, $tokenStorage, self::ENTRY_POINT);
        $listener->onNavigationConfigure($this->createEventMock());
    }

    /**
     * @test
     */
    public function shouldDoNotAddMenuItemIfMenuDoesNotHaveSystemTab()
    {
        $authorizationChecker = $this->createAuthorizationCheckerMock();
        $tokenStorage = $this->createTokenStorageMock();

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(true));
        $authorizationChecker->expects($this->once())
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

        $listener = new NavigationListener($authorizationChecker, $tokenStorage, self::ENTRY_POINT);
        $listener->onNavigationConfigure($event);
    }

    /**
     * @test
     */
    public function shouldAddMenuItem()
    {
        $authorizationChecker = $this->createAuthorizationCheckerMock();
        $tokenStorage = $this->createTokenStorageMock();

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(true));
        $authorizationChecker->expects($this->once())
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

        $listener = new NavigationListener($authorizationChecker, $tokenStorage, self::ENTRY_POINT);
        $listener->onNavigationConfigure($event);
    }

    /**
     * @test
     */
    public function shouldAddMenuItemForSubdirectory()
    {
        $authorizationChecker = $this->createAuthorizationCheckerMock();
        $tokenStorage = $this->createTokenStorageMock();

        $listener = new NavigationListener($authorizationChecker, $tokenStorage, self::ENTRY_POINT);

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(true));
        $authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $request = $this->createRequestMock();
        $request->expects($this->once())
            ->method('getBasePath')
            ->will($this->returnValue('/subdir'));

        $listener->setRequest($request);

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
                    'uri'            => '/subdir/' . self::ENTRY_POINT,
                    'linkAttributes' => ['class' => 'no-hash'],
                    'extras'         => ['position' => '110']
                ]
            )
            ->will($this->returnValue($systemTab));

        $listener->onNavigationConfigure($event);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createMenuItemMock()
    {
        return $this->createConstructorLessMock('Knp\Menu\ItemInterface');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createEventMock()
    {
        return $this->createConstructorLessMock('Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createAuthorizationCheckerMock()
    {
        return $this->createMock('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createTokenStorageMock()
    {
        return $this->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createRequestMock()
    {
        return $this->createConstructorLessMock('Symfony\Component\HttpFoundation\Request');
    }
}
