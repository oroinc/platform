<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\EventListener;

use Knp\Menu\ItemInterface;
use Oro\Bundle\DashboardBundle\EventListener\NavigationListener;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class NavigationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var NavigationListener */
    private $navigationListener;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->manager = $this->createMock(Manager::class);

        $this->navigationListener = new NavigationListener(
            $this->tokenAccessor,
            $this->manager
        );
    }

    public function testOnNavigationConfigureWithoutUser()
    {
        $event = $this->createMock(ConfigureMenuEvent::class);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('hasUser')
            ->willReturn(false);
        $this->manager->expects($this->never())
            ->method('findAllowedDashboardsShortenedInfo');

        $this->navigationListener->onNavigationConfigure($event);
    }

    public function testOnNavigationConfigureWithDisabledDisplaying()
    {
        $event = $this->createMock(ConfigureMenuEvent::class);

        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $menu = $this->createMock(ItemInterface::class);
        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->once())
            ->method('isDisplayed')
            ->willReturn(false);

        $event->expects($this->once())
            ->method('getMenu')
            ->willReturn($menu);

        $this->manager->expects($this->never())
            ->method('findAllowedDashboardsShortenedInfo');

        $menu->expects($this->once())
            ->method('getChild')
            ->with('dashboard_tab')->willReturn($item);

        $this->navigationListener->onNavigationConfigure($event);
    }

    public function testOnNavigationConfigureAddCorrectItems()
    {
        $event = $this->createMock(ConfigureMenuEvent::class);

        $id = 42;
        $secondId = 43;
        $expectedLabel = 'expected label';
        $secondExpectedLabel = 'test expected label';

        $dashboards = [
            ['id' => $id, 'label' => $expectedLabel],
            ['id' => $secondId, 'label' => $secondExpectedLabel],
        ];
        $menuItemAlias = $id . '_dashboard_menu_item';
        $secondMenuItemAlias = $secondId . '_dashboard_menu_item';

        $expectedOptions = [
            'label'           => $expectedLabel,
            'route'           => 'oro_dashboard_view',
            'extras'          => [
                'translate_disabled' => true,
                'position' => 2
            ],
            'routeParameters' => [
                'id'               => $id,
                'change_dashboard' => true
            ]
        ];
        $secondExpectedOptions = [
            'label'           => $secondExpectedLabel,
            'route'           => 'oro_dashboard_view',
            'extras'          => [
                'translate_disabled' => true,
                'position' => 2
            ],
            'routeParameters' => [
                'id'               => $secondId,
                'change_dashboard' => true
            ]
        ];

        $menu = $this->createMock(ItemInterface::class);
        $item = $this->createMock(ItemInterface::class);
        $child = $this->createMock(ItemInterface::class);
        $child->expects($this->atLeastOnce())
            ->method('setAttribute')
            ->with('data-menu')
            ->willReturnSelf();

        $divider = $this->createMock(ItemInterface::class);
        $divider->expects($this->once())
            ->method('setLabel')
            ->with('')
            ->willReturnSelf();
        $divider->expects($this->once())
            ->method('setAttribute')
            ->with('class', 'menu-divider')
            ->willReturnSelf();
        $divider->expects($this->exactly(2))
            ->method('setExtra')
            ->willReturnMap([
                ['position', 3, $divider],
                ['divider', true, $divider]
            ]);

        $item->expects($this->exactly(3))
            ->method('addChild')
            ->withConsecutive(
                [$menuItemAlias, $expectedOptions],
                [$secondMenuItemAlias, $secondExpectedOptions],
                []
            )
            ->willReturnOnConsecutiveCalls(
                $child,
                $child,
                $divider
            );

        $menu->expects($this->once())
            ->method('getChild')
            ->willReturn($item);
        $item->expects($this->once())
            ->method('isDisplayed')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getMenu')
            ->willReturn($menu);
        $this->tokenAccessor->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);
        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(null);
        $this->manager->expects($this->once())
            ->method('findAllowedDashboardsShortenedInfo')
            ->willReturn($dashboards);

        $this->navigationListener->onNavigationConfigure($event);
    }
}
