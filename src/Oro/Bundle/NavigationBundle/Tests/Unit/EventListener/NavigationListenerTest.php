<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\EventListener\NavigationListener;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class NavigationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var NavigationListener */
    protected $navigationListener;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->navigationListener = new NavigationListener(
            $this->authorizationChecker,
            $this->tokenAccessor
        );
    }

    public function testDisplayIsSet()
    {
        $item = $this->createMock('Knp\Menu\ItemInterface');
        $menu = $this->createMock('Knp\Menu\ItemInterface');

        /** @var ConfigureMenuEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder('\Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())->method('getMenu')->willReturn($menu);
        $menu->expects($this->once())->method('getChild')->willReturn($item);
        $item->expects($this->once())->method('setDisplay')->with(false);
        $this->tokenAccessor->expects($this->once())->method('hasUser')->willReturn(true);
        $this->authorizationChecker->expects($this->once())->method('isGranted')->willReturn(false);

        $this->navigationListener->onNavigationConfigure($event);
    }

    /**
     * @dataProvider displayIsNotSetProvider
     *
     * @param ItemInterface|null $item
     * @param bool $hasUser
     * @param bool $isGranted
     */
    public function testDisplayIsNotSet($item, $hasUser, $isGranted)
    {
        $menu = $this->createMock('Knp\Menu\ItemInterface');

        /** @var ConfigureMenuEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder('\Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())->method('getMenu')->willReturn($menu);
        $menu->expects($this->any())->method('getChild')->willReturn($item);
        $menu->expects($this->any())->method('getChildren')->willReturn([]);
        $this->tokenAccessor->expects($this->any())->method('hasUser')->willReturn($hasUser);
        $this->authorizationChecker->expects($this->any())->method('isGranted')->willReturn($isGranted);

        $this->navigationListener->onNavigationConfigure($event);
    }

    public function displayIsNotSetProvider()
    {
        return [
            'with empty item' => [
                'item' => null,
                'hasUser' => true,
                'isGranted' => true,
            ],
            'with no logged user' => [
                'item' => $this->createMock('Knp\Menu\ItemInterface'),
                'hasUser' => false,
                'isGranted' => true,
            ],
            'with no access rights' => [
                'item' => $this->createMock('Knp\Menu\ItemInterface'),
                'hasUser' => true,
                'isGranted' => false,
            ],
        ];
    }

    public function testOnNavigationConfigureWithoutToken()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $factory = new MenuFactory();
        $menu  = new MenuItem('parent_item', $factory);
        $menuListItem = new MenuItem('menu_list_default', $factory);
        $menu->addChild($menuListItem);

        $this->navigationListener->onNavigationConfigure(new ConfigureMenuEvent($factory, $menu));

        $this->assertTrue($menuListItem->isDisplayed());
    }

    public function testOnNavigationConfigureWhenOroConfigSystemIsNotGnanted()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnMap(
                [
                    ['oro_config_system', null, false],
                    ['oro_navigation_manage_menus', null, true]
                ]
            );

        $factory     = new MenuFactory();
        $menu  = new MenuItem('parent_item', $factory);
        $menuListItem = new MenuItem('menu_list_default', $factory);
        $menu->addChild($menuListItem);

        $this->navigationListener->onNavigationConfigure(new ConfigureMenuEvent($factory, $menu));

        $this->assertFalse($menuListItem->isDisplayed());
    }

    public function testOnNavigationConfigureWhenOroNavigationManageMenusIsNotGranted()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnMap(
                [
                    ['oro_config_system', null, true],
                    ['oro_navigation_manage_menus', null, false]
                ]
            );

        $factory = new MenuFactory();
        $menu  = new MenuItem('parent_item', $factory);
        $menuListItem = new MenuItem('menu_list_default', $factory);
        $menu->addChild($menuListItem);

        $this->navigationListener->onNavigationConfigure(new ConfigureMenuEvent($factory, $menu));

        $this->assertFalse($menuListItem->isDisplayed());
    }

    public function testOnNavigationConfigureWhenAccessIsGranted()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnMap(
                [
                    ['oro_config_system', null, true],
                    ['oro_navigation_manage_menus', null, true]
                ]
            );

        $factory = new MenuFactory();
        $menu  = new MenuItem('parent_item', $factory);
        $menuListItem = new MenuItem('menu_list_default', $factory);
        $menu->addChild($menuListItem);

        $this->navigationListener->onNavigationConfigure(new ConfigureMenuEvent($factory, $menu));

        $this->assertTrue($menuListItem->isDisplayed());
    }
}
