<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use Oro\Bundle\NavigationBundle\Menu\ChainBreadcrumbManager;
use Symfony\Component\Routing\Route;

class ChainBreadcrumbManagerTest extends \PHPUnit\Framework\TestCase
{
    const MENU_NAME = 'test_menu';

    /**
     * @var ChainBreadcrumbManager
     */
    protected $manager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|BreadcrumbManager
     */
    protected $managerMock;

    /**
     * @var \Knp\Menu\FactoryInterface|\PHPUnit\Framework\MockObject\MockObject
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
        $this->manager = new ChainBreadcrumbManager();
        $this->manager->setDefaultManager($this->getManagerMock());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|BreadcrumbManagerInterface
     */
    protected function getManagerMock()
    {
        return $this->createMock('Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface');
    }

    public function testAddProvider()
    {
        $supportedManager = $this->getManagerMock();
        $supportedManager->expects($this->once())
            ->method('supports')
            ->willReturn(true);

        $unsupportedManager = $this->getManagerMock();
        $unsupportedManager->expects($this->once())
            ->method('supports')
            ->willReturn(false);

        $this->manager->addManager($unsupportedManager);
        $this->manager->addManager($supportedManager);

        $this->assertEquals($supportedManager, $this->manager->getSupportedManager());
    }

    public function testSetDefaultProvider()
    {
        $manager = $this->getManagerMock();

        $this->manager->setDefaultManager($manager);

        $this->assertEquals($manager, $this->manager->getSupportedManager());
    }

    public function testGetBreadcrumbs()
    {
        $manager = $this->getManagerMock();
        $manager->expects($this->once())
            ->method('getBreadcrumbs')
            ->with(self::MENU_NAME);

        $this->manager->setDefaultManager($manager);
        $this->manager->getBreadcrumbs(self::MENU_NAME);
    }

    public function testGetMenu()
    {
        $manager = $this->getManagerMock();
        $manager->expects($this->once())
            ->method('getMenu')
            ->with(self::MENU_NAME);

        $this->manager->setDefaultManager($manager);
        $this->manager->getMenu(self::MENU_NAME);
    }

    public function testGetBreadcrumbArray()
    {
        $menuItem = new MenuItem('test', $this->factory);

        $manager = $this->getManagerMock();
        $manager->expects($this->once())
            ->method('getBreadcrumbArray')
            ->with(self::MENU_NAME, $menuItem);

        $this->manager->setDefaultManager($manager);
        $this->manager->getBreadcrumbArray(self::MENU_NAME, $menuItem);
    }

    public function testGetCurrentMenuItem()
    {
        $manager = $this->getManagerMock();
        $manager->expects($this->once())
            ->method('getCurrentMenuItem')
            ->with(self::MENU_NAME);

        $this->manager->setDefaultManager($manager);
        $this->manager->getCurrentMenuItem(self::MENU_NAME);
    }

    public function testGetBreadcrumbLabels()
    {
        $route = new Route('test_route');
        $manager = $this->getManagerMock();
        $manager->expects($this->once())
            ->method('getBreadcrumbLabels')
            ->with(self::MENU_NAME, $route);

        $this->manager->setDefaultManager($manager);
        $this->manager->getBreadcrumbLabels(self::MENU_NAME, $route);
    }

    public function testSupports()
    {
        $manager = $this->getManagerMock();
        $supports = true;
        $manager->expects($this->once())->method('supports')->willReturn($supports);

        $this->manager->addManager($manager);

        $this->assertEquals($supports, $this->manager->supports());
    }

    public function testSupportsDefault()
    {
        $manager = $this->getManagerMock();
        $supports = true;
        $manager->expects($this->never())->method('supports')->willReturn($supports);

        $this->manager->setDefaultManager($manager);

        $this->assertEquals($supports, $this->manager->supports());
    }
}
