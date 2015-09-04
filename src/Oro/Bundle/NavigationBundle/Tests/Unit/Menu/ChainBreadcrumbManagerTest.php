<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\MenuItem;

use Oro\Bundle\NavigationBundle\Menu\ChainBreadcrumbManager;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager;

use Symfony\Component\Routing\Route;

class ChainBreadcrumbManagerTest extends \PHPUnit_Framework_TestCase
{
    const MENU_NAME = 'test_menu';

    /**
     * @var ChainBreadcrumbManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|BreadcrumbManager
     */
    protected $providerMock;

    /**
     * @var \Knp\Menu\FactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->factory = $this->getMockBuilder('Knp\Menu\MenuFactory')
            ->setMethods(['getRouteInfo', 'processRoute'])
            ->getMock();
        $this->manager = new ChainBreadcrumbManager($this->getProviderMock());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager
     */
    protected function getProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\NavigationBundle\Tests\Unit\Menu\ChainBreadcrumbProviderStub')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAddProvider()
    {
        $supportedProvider = $this->getProviderMock();
        $supportedProvider->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $unsupportedProvider = $this->getProviderMock();
        $unsupportedProvider->expects($this->once())
            ->method('supports')
            ->willReturn(false);

        $this->manager->addProvider($unsupportedProvider);
        $this->manager->addProvider($supportedProvider);

        $this->assertEquals($supportedProvider, $this->manager->getSupportedProvider());

    }

    public function testSetDefaultProvider()
    {
        $provider = $this->getProviderMock();

        $this->manager->setDefaultProvider($provider);

        $this->assertEquals($provider, $this->manager->getSupportedProvider());
    }

    public function testGetBreadcrumbs()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())
            ->method('getBreadcrumbs')
            ->with(self::MENU_NAME);

        $this->manager->setDefaultProvider($provider);
        $this->manager->getBreadcrumbs(self::MENU_NAME);
    }

    public function testGetMenu()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())
            ->method('getMenu')
            ->with(self::MENU_NAME);

        $this->manager->setDefaultProvider($provider);
        $this->manager->getMenu(self::MENU_NAME);
    }


    public function testGetBreadcrumbArray()
    {
        $menuItem = new MenuItem('test', $this->factory);

        $provider = $this->getProviderMock();
        $provider->expects($this->once())
            ->method('getBreadcrumbArray')
            ->with(self::MENU_NAME, $menuItem);

        $this->manager->setDefaultProvider($provider);
        $this->manager->getBreadcrumbArray(self::MENU_NAME, $menuItem);

    }

    public function testGetCurrentMenuItem()
    {
        $provider = $this->getProviderMock();
        $provider->expects($this->once())
            ->method('getCurrentMenuItem')
            ->with(self::MENU_NAME);

        $this->manager->setDefaultProvider($provider);
        $this->manager->getCurrentMenuItem(self::MENU_NAME);
    }

    public function testGetBreadcrumbLabels()
    {
        $route = new Route('test_route');
        $provider = $this->getProviderMock();
        $provider->expects($this->once())
            ->method('getBreadcrumbLabels')
            ->with(self::MENU_NAME, $route);

        $this->manager->setDefaultProvider($provider);
        $this->manager->getBreadcrumbLabels(self::MENU_NAME, $route);

    }
}
