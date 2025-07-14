<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Menu\NavigationMostviewedBuilder;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NavigationMostviewedBuilderTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private NavigationItemsProviderInterface&MockObject $navigationItemsProvider;
    private ConfigManager&MockObject $configManager;
    private NavigationMostviewedBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->navigationItemsProvider = $this->createMock(NavigationItemsProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->builder = new NavigationMostviewedBuilder(
            $this->tokenAccessor,
            $this->navigationItemsProvider,
            $this->configManager
        );
    }

    public function testBuild(): void
    {
        $organization = new Organization();
        $type = 'mostviewed';
        $maxItems = 20;

        $user = $this->createMock(User::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->navigationItemsProvider->expects($this->once())
            ->method('getNavigationItems')
            ->with(
                $user,
                $organization,
                $type,
                [
                    'max_items' => $maxItems,
                    'order_by' => [['field' => NavigationHistoryItem::NAVIGATION_HISTORY_COLUMN_VISIT_COUNT]],
                ]
            )
            ->willReturn([]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_navigation.max_items')
            ->willReturn($maxItems);

        $menu = $this->createMock(ItemInterface::class);

        $this->builder->build($menu, [], $type);
    }
}
