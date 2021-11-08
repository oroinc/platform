<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Model\Factory;
use Oro\Bundle\DashboardBundle\Model\StateManager;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var StateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $stateManager;

    /** @var WidgetConfigs|\PHPUnit\Framework\MockObject\MockObject */
    private $widgetConfigs;

    /** @var Factory */
    private $dashboardFactory;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->stateManager = $this->createMock(StateManager::class);
        $this->widgetConfigs = $this->createMock(WidgetConfigs::class);

        $this->dashboardFactory = new Factory(
            $this->configProvider,
            $this->stateManager,
            $this->widgetConfigs
        );
    }

    public function testCreateDashboardModel()
    {
        $expectedConfig = ['label' => 'test label'];

        $name = 'test';
        $dashboard = $this->createMock(Dashboard::class);
        $dashboard->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->configProvider->expects($this->once())
            ->method('getDashboardConfig')
            ->with($name)
            ->willReturn($expectedConfig);

        $result = $this->dashboardFactory->createDashboardModel($dashboard);
        $this->assertEquals($expectedConfig, $result->getConfig());
        $this->assertSame($dashboard, $result->getEntity());
    }

    public function testCreateDashboardModelWithoutConfig()
    {
        $expectedConfig = [];

        $dashboard = $this->createMock(Dashboard::class);
        $dashboard->expects($this->once())
            ->method('getName')
            ->willReturn(null);

        $this->configProvider->expects($this->never())
            ->method('getDashboardConfig')
            ->willReturn($expectedConfig);

        $result = $this->dashboardFactory->createDashboardModel($dashboard);
        $this->assertEquals($expectedConfig, $result->getConfig());
        $this->assertSame($dashboard, $result->getEntity());
    }

    public function testCreateWidgetModel()
    {
        $expectedConfig = ['label' => 'test label'];

        $name = 'test';
        $widget = $this->createMock(Widget::class);
        $widget->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($name)
            ->willReturn($expectedConfig);

        $widgetState = $this->createMock(WidgetState::class);
        $this->stateManager->expects($this->once())
            ->method('getWidgetState')
            ->with($widget)
            ->willReturn($widgetState);

        $result = $this->dashboardFactory->createWidgetModel($widget);
        $this->assertEquals($expectedConfig, $result->getConfig());
        $this->assertSame($widget, $result->getEntity());
    }

    public function testCreateVisibleWidgetModel()
    {
        $widgetName = 'widget-name';
        $widgetConfig = [
            'label' => 'Widget label',
        ];
        $widget = (new Widget())
            ->setName($widgetName);

        $this->widgetConfigs->expects($this->once())
            ->method('getWidgetConfig')
            ->with($widget->getName())
            ->willReturn($widgetConfig);

        $this->stateManager->expects($this->once())
            ->method('getWidgetState')
            ->with($widget)
            ->willReturn($this->createMock(WidgetState::class));

        $result = $this->dashboardFactory->createVisibleWidgetModel($widget);
        $this->assertNotNull($result);
        $this->assertEquals($widgetConfig, $result->getConfig());
        $this->assertSame($widget, $result->getEntity());
    }
}
