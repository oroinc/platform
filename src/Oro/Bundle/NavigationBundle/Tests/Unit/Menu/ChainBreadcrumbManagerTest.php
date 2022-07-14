<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use Oro\Bundle\NavigationBundle\Menu\ChainBreadcrumbManager;
use Symfony\Component\Routing\Route;

class ChainBreadcrumbManagerTest extends \PHPUnit\Framework\TestCase
{
    private const MENU_NAME = 'test_menu';

    /** @var FactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(MenuFactory::class);
    }

    public function testGetBreadcrumbs()
    {
        $manager = $this->createMock(BreadcrumbManagerInterface::class);
        $manager->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('getBreadcrumbs')
            ->with(self::MENU_NAME);

        $manager = new ChainBreadcrumbManager([$manager]);
        $manager->getBreadcrumbs(self::MENU_NAME);
    }

    public function testGetBreadcrumbsWithRoute()
    {
        $validRoute = 'valid_route';
        $invalidRoute = 'invalid_route';

        $manager1 = $this->createMock(BreadcrumbManagerInterface::class);
        $manager1->expects($this->once())
            ->method('supports')
            ->with($validRoute)
            ->willReturn(false);
        $manager1->expects($this->never())
            ->method('getBreadcrumbs')
            ->with(self::MENU_NAME)
            ->willReturn(['manager1_breadcrumbs']);

        $manager2 = $this->createMock(BreadcrumbManagerInterface::class);
        $manager2->expects($this->once())
            ->method('supports')
            ->with($validRoute)
            ->willReturn(true);
        $manager2->expects($this->once())
            ->method('getBreadcrumbs')
            ->with(self::MENU_NAME)
            ->willReturn(['manager2_breadcrumbs']);

        $manager = new ChainBreadcrumbManager([$manager1, $manager2]);
        $breadcrumbs = $manager->getBreadcrumbs(self::MENU_NAME, true, $validRoute);
        $this->assertEquals(['manager2_breadcrumbs'], $breadcrumbs);

        $manager1 = $this->createMock(BreadcrumbManagerInterface::class);
        $manager1->expects($this->once())
            ->method('supports')
            ->willReturn(false);
        $manager1->expects($this->never())
            ->method('getBreadcrumbs')
            ->with(self::MENU_NAME);

        $manager2 = $this->createMock(BreadcrumbManagerInterface::class);
        $manager2->expects($this->once())
            ->method('supports')
            ->willReturn(false);
        $manager2->expects($this->never())
            ->method('getBreadcrumbs')
            ->with(self::MENU_NAME);

        $manager = new ChainBreadcrumbManager([$manager1, $manager2]);
        $this->expectErrorMessage('A breadcrumb manager was not found.');
        $manager->getBreadcrumbs(self::MENU_NAME);

        $manager1 = $this->createMock(BreadcrumbManagerInterface::class);
        $manager1->expects($this->once())
            ->method('supports')
            ->with($invalidRoute)
            ->willReturn(false);
        $manager1->expects($this->never())
            ->method('getBreadcrumbs')
            ->with(self::MENU_NAME);

        $manager2 = $this->createMock(BreadcrumbManagerInterface::class);
        $manager2->expects($this->once())
            ->method('supports')
            ->with($invalidRoute)
            ->willReturn(false);
        $manager2->expects($this->never())
            ->method('getBreadcrumbs')
            ->with(self::MENU_NAME);

        $manager = new ChainBreadcrumbManager([$manager1, $manager2]);
        $this->expectErrorMessage('A breadcrumb manager was not found.');
        $manager->getBreadcrumbs(self::MENU_NAME, true, 'invalid_route');
    }

    public function testGetMenu()
    {
        $manager = $this->createMock(BreadcrumbManagerInterface::class);
        $manager->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('getMenu')
            ->with(self::MENU_NAME);

        $manager = new ChainBreadcrumbManager([$manager]);
        $manager->getMenu(self::MENU_NAME);
    }

    public function testGetBreadcrumbArray()
    {
        $menuItem = new MenuItem('test', $this->factory);

        $manager = $this->createMock(BreadcrumbManagerInterface::class);
        $manager->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('getBreadcrumbArray')
            ->with(self::MENU_NAME, $menuItem);

        $manager = new ChainBreadcrumbManager([$manager]);
        $manager->getBreadcrumbArray(self::MENU_NAME, $menuItem);
    }

    public function testGetCurrentMenuItem()
    {
        $manager = $this->createMock(BreadcrumbManagerInterface::class);
        $manager->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('getCurrentMenuItem')
            ->with(self::MENU_NAME);

        $manager = new ChainBreadcrumbManager([$manager]);
        $manager->getCurrentMenuItem(self::MENU_NAME);
    }

    public function testGetBreadcrumbLabels()
    {
        $route = new Route('test_route');

        $manager = $this->createMock(BreadcrumbManagerInterface::class);
        $manager->expects($this->once())
            ->method('supports')
            ->with($route)
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('getBreadcrumbLabels')
            ->with(self::MENU_NAME, $route);

        $manager = new ChainBreadcrumbManager([$manager]);
        $manager->getBreadcrumbLabels(self::MENU_NAME, $route);
    }

    public function testSupports()
    {
        $manager = $this->createMock(BreadcrumbManagerInterface::class);
        $manager->expects($this->once())
            ->method('supports')
            ->willReturn(true);
        $manager->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $manager = new ChainBreadcrumbManager([$manager]);
        $this->assertTrue($manager->supports());
    }

    public function testSupportsWhenNoManagersSupportRequest()
    {
        $route = 'test';

        $manager = $this->createMock(BreadcrumbManagerInterface::class);
        $manager->expects($this->once())
            ->method('supports')
            ->with($route)
            ->willReturn(false);

        $manager = new ChainBreadcrumbManager([$manager]);
        $this->assertFalse($manager->supports($route));
    }
}
