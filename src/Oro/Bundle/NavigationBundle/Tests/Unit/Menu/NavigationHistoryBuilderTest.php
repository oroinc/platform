<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Menu\NavigationHistoryBuilder;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class NavigationHistoryBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var NavigationItemsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $navigationItemsProvider;

    /** @var Matcher|\PHPUnit\Framework\MockObject\MockObject */
    private $matcher;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var MenuManipulator|\PHPUnit\Framework\MockObject\MockObject */
    private $menuManipulator;

    /** @var NavigationHistoryBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->navigationItemsProvider = $this->createMock(NavigationItemsProviderInterface::class);
        $this->matcher = $this->createMock(Matcher::class);
        $this->menuManipulator = $this->createMock(MenuManipulator::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->builder = new NavigationHistoryBuilder(
            $this->tokenAccessor,
            $this->navigationItemsProvider,
            $this->matcher,
            $this->menuManipulator,
            $this->configManager
        );
    }

    public function testBuild(): void
    {
        $organization = new Organization();
        $type = 'history';

        $user = $this->createMock(User::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->navigationItemsProvider->expects(self::once())
            ->method('getNavigationItems')
            ->with($user, $organization, $type)
            ->willReturn($items = [
                ['id' => 1, 'title' => 'sample-title-1', 'url' => '', 'route' => 'sample_route_1', 'type' => $type],
                ['id' => 2, 'title' => 'sample-title-2', 'url' => '', 'route' => 'sample_route_2', 'type' => $type],
            ]);

        $menu = $this->createMock(\Knp\Menu\MenuItem::class);

        $childMock = $this->createMock(\Knp\Menu\ItemInterface::class);
        $childMock2 = clone $childMock;
        $children = [$childMock, $childMock2];

        $this->matcher->expects($this->once())
            ->method('isCurrent')
            ->willReturn(true);

        $menu->expects($this->exactly(2))
            ->method('addChild');
        $menu->expects($this->once())
            ->method('setExtra')
            ->with('type', $type);
        $menu->expects($this->once())
            ->method('getChildren')
            ->willReturn($children);
        $menu->expects($this->once())
            ->method('removeChild');

        $n = random_int(1, 10);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_navigation.max_items')
            ->willReturn($n);

        $this->menuManipulator->expects($this->once())
            ->method('slice')
            ->with($menu, 0, $n);

        $this->builder->build($menu, [], $type);
    }
}
