<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\DashboardType\WidgetsDashboardTypeConfigProvider;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Model\Factory;
use Oro\Bundle\DashboardBundle\Model\StateManager;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\DashboardType\DashboardTestType;
use Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity\DashboardWithType;
use Oro\Bundle\EntityExtendBundle\Model\EnumValue;

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
            $this->widgetConfigs,
            [
                new WidgetsDashboardTypeConfigProvider($this->configProvider),
                new DashboardTestType()
            ]
        );
    }

    public function testCreateDashboardModel(): void
    {
        $expectedConfig = ['label' => 'test label'];

        $name = 'test';
        $dashboard = $this->createMock(Dashboard::class);
        $dashboard->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        $this->configProvider->expects(self::once())
            ->method('getDashboardConfig')
            ->with($name)
            ->willReturn($expectedConfig);

        $result = $this->dashboardFactory->createDashboardModel($dashboard);
        self::assertEquals($expectedConfig, $result->getConfig());
        self::assertSame($dashboard, $result->getEntity());
    }

    public function testCreateDashboardModelWithoutConfig(): void
    {
        $expectedConfig = [];

        $dashboard = $this->createMock(Dashboard::class);
        $dashboard->expects(self::once())
            ->method('getName')
            ->willReturn(null);

        $this->configProvider->expects(self::never())
            ->method('getDashboardConfig')
            ->willReturn($expectedConfig);

        $result = $this->dashboardFactory->createDashboardModel($dashboard);
        self::assertEquals($expectedConfig, $result->getConfig());
        self::assertSame($dashboard, $result->getEntity());
    }

    public function testCreateDashboardModelForTestDashboardType(): void
    {
        $expectedConfig = ['template' => 'test'];

        $dashboardType = new EnumValue();
        $dashboardType->setId('test');

        $dashboard = new DashboardWithType();
        $dashboard->setDashboardType($dashboardType);

        $this->configProvider->expects(self::never())
            ->method('getDashboardConfig');

        $result = $this->dashboardFactory->createDashboardModel($dashboard);
        self::assertEquals($expectedConfig, $result->getConfig());
        self::assertSame($dashboard, $result->getEntity());
    }

    public function testCreateDashboardModelForWidgetsType(): void
    {
        $expectedConfig = ['label' => 'test label'];

        $name = 'test';
        $dashboardType = new EnumValue();
        $dashboardType->setId('widgets');
        $dashboard = new DashboardWithType();
        $dashboard->setDashboardType($dashboardType);
        $dashboard->setName($name);

        $this->configProvider->expects(self::once())
            ->method('getDashboardConfig')
            ->with($name)
            ->willReturn($expectedConfig);

        $result = $this->dashboardFactory->createDashboardModel($dashboard);
        self::assertEquals($expectedConfig, $result->getConfig());
        self::assertSame($dashboard, $result->getEntity());
    }

    public function testCreateWidgetModel(): void
    {
        $expectedConfig = ['label' => 'test label'];

        $name = 'test';
        $widget = $this->createMock(Widget::class);
        $widget->expects(self::once())
            ->method('getName')
            ->willReturn($name);

        $this->configProvider->expects(self::once())
            ->method('getWidgetConfig')
            ->with($name)
            ->willReturn($expectedConfig);

        $widgetState = $this->createMock(WidgetState::class);
        $this->stateManager->expects(self::once())
            ->method('getWidgetState')
            ->with($widget)
            ->willReturn($widgetState);

        $result = $this->dashboardFactory->createWidgetModel($widget);
        self::assertEquals($expectedConfig, $result->getConfig());
        self::assertSame($widget, $result->getEntity());
    }

    public function testCreateVisibleWidgetModel(): void
    {
        $widgetName = 'widget-name';
        $widgetConfig = [
            'label' => 'Widget label',
        ];
        $widget = (new Widget())->setName($widgetName);

        $this->widgetConfigs->expects(self::once())
            ->method('getWidgetConfig')
            ->with($widget->getName())
            ->willReturn($widgetConfig);

        $this->stateManager->expects(self::once())
            ->method('getWidgetState')
            ->with($widget)
            ->willReturn($this->createMock(WidgetState::class));

        $result = $this->dashboardFactory->createVisibleWidgetModel($widget);
        self::assertNotNull($result);
        self::assertEquals($widgetConfig, $result->getConfig());
        self::assertSame($widget, $result->getEntity());
    }
}
