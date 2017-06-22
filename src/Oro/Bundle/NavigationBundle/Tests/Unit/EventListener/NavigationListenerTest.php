<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Knp\Menu\ItemInterface;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\EventListener\NavigationListener;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class NavigationListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var NavigationListener */
    protected $navigationListener;

    /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit_Framework_MockObject_MockObject */
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

        /** @var ConfigureMenuEvent|\PHPUnit_Framework_MockObject_MockObject $event */
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

        /** @var ConfigureMenuEvent|\PHPUnit_Framework_MockObject_MockObject $event */
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
}
