<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\ItemInterface as KnpItemInterface;
use Oro\Bundle\NavigationBundle\Menu\NavigationItemBuilder;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class NavigationItemBuilderTest extends \PHPUnit\Framework\TestCase
{
    private const ITEM_TYPE = 'favorite';

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var NavigationItemsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $navigationItemsProvider;

    /** @var KnpItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $menu;

    /** @var NavigationItemBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->navigationItemsProvider = $this->createMock(NavigationItemsProviderInterface::class);
        $this->menu = $this->createMock(KnpItemInterface::class);

        $this->builder = new NavigationItemBuilder($this->tokenAccessor, $this->navigationItemsProvider);
    }

    public function testBuildAnonUser(): void
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->tokenAccessor->expects($this->never())
            ->method('getOrganization');

        $this->menu->expects($this->never())
            ->method('addChild');

        $this->menu->expects($this->once())
            ->method('setExtra')
            ->with('type', 'pinbar');

        $this->builder->build($this->menu, [], 'pinbar');
    }

    public function testBuild(): void
    {
        $organization = new Organization();
        $user = $this->createMock(User::class);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->navigationItemsProvider->expects(self::once())
            ->method('getNavigationItems')
            ->with($user, $organization, self::ITEM_TYPE)
            ->willReturn($items = [
                ['id' => 1, 'title' => 'sample-title-1', 'url' => 'sample-url-1', 'type' => self::ITEM_TYPE],
                ['id' => 2, 'title' => 'sample-title-2', 'url' => 'sample-url-2', 'type' => self::ITEM_TYPE],
            ]);

        $this->menu->expects($this->once())
            ->method('setExtra')
            ->with('type', self::ITEM_TYPE);

        $this->menu->expects($this->exactly(2))
            ->method('addChild')
            ->withConsecutive(
                [
                    self::ITEM_TYPE . '_item_1',
                    [
                        'extras' => $items[0],
                        'uri' => 'sample-url-1',
                        'label' => 'sample-title-1',
                    ],
                ],
                [
                    self::ITEM_TYPE . '_item_2',
                    [
                        'extras' => $items[1],
                        'uri' => 'sample-url-2',
                        'label' => 'sample-title-2',
                    ],
                ]
            );

        $this->builder->build($this->menu, [], self::ITEM_TYPE);
    }
}
