<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\NavigationBundle\Layout\DataProvider\BreadcrumbProvider;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BreadcrumbProviderTest extends TestCase
{
    private BreadcrumbManagerInterface&MockObject $breadcrumbManager;
    private BreadcrumbProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->breadcrumbManager = $this->createMock(BreadcrumbManagerInterface::class);

        $this->provider = new BreadcrumbProvider($this->breadcrumbManager);
    }

    public function testGetBreadcrumbs(): void
    {
        $menuName = 'customer_usermenu';
        $breadcrumbs = [
            [
                'label' => 'oro.customer.menu.customer_user.label',
                'url'   => '/customer/user/',
                'item'  => null
            ],
            [
                'label' => 'oro.customer.frontend.customer_user_address_book.customer_addresses',
                'url'   => '/customer/user/address/',
                'item'  => null
            ]
        ];
        $this->breadcrumbManager->expects($this->once())
            ->method('getBreadcrumbs')
            ->with($menuName, true)
            ->willReturn($breadcrumbs);

        $result = $this->provider->getBreadcrumbs($menuName);
        $this->assertEquals($breadcrumbs, $result);
    }
}
