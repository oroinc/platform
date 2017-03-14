<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\NavigationBundle\Layout\DataProvider\BreadcrumbProvider;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use Oro\Component\DependencyInjection\ServiceLink;

class BreadcrumbProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BreadcrumbProvider
     */
    private $provider;

    /**
     * @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject
     */
    private $breadcrumbManagerLink;

    public function setUp()
    {
        $this->breadcrumbManagerLink = $this->createMock(ServiceLink::class);
        $this->provider              = new BreadcrumbProvider($this->breadcrumbManagerLink);
    }

    public function testGetBreadcrumbs()
    {
        $breadcrumbManager = $this->createMock(BreadcrumbManagerInterface::class);
        $menuName          = 'customer_usermenu';
        $breadcrumbs       = [
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
        $breadcrumbManager->expects($this->once())
            ->method('getBreadcrumbs')
            ->with($menuName, false)
            ->willReturn($breadcrumbs);

        $this->breadcrumbManagerLink->expects($this->once())
            ->method('getService')
            ->willReturn($breadcrumbManager);

        $result = $this->provider->getBreadcrumbs($menuName);
        $this->assertEquals($breadcrumbs, $result);
    }
}
