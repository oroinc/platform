<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Model\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Factory
     */
    protected $dashboardFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Oro\Bundle\DashboardBundle\Model\ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Oro\Bundle\DashboardBundle\Model\StateManager
     */
    protected $stateManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Oro\Component\Config\Resolver\ResolverInterface
     */
    protected $resolver;

    protected function setUp()
    {
        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\DashboardBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->stateManager = $this
            ->getMockBuilder('Oro\Bundle\DashboardBundle\Model\StateManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = $this->getMock('Oro\Component\Config\Resolver\ResolverInterface');
        $this->resolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValueMap([
                [['applicable'], [], [true]],
                [['inapplicable'], [], [false]],
            ]));

        $this->dashboardFactory = new Factory(
            $this->configProvider,
            $this->stateManager,
            $this->resolver
        );
    }

    public function testCreateDashboardModel()
    {
        $expectedConfig = array('label' => 'test label');

        $name      = 'test';
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboard->expects($this->once())->method('getName')->will($this->returnValue($name));

        $this->configProvider->expects($this->once())
            ->method('getDashboardConfig')
            ->with($name)
            ->will($this->returnValue($expectedConfig));

        $result = $this->dashboardFactory->createDashboardModel($dashboard);
        $this->assertEquals($expectedConfig, $result->getConfig());
        $this->assertSame($dashboard, $result->getEntity());
    }

    public function testCreateDashboardModelWithoutConfig()
    {
        $expectedConfig = array();

        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboard->expects($this->once())->method('getName')->will($this->returnValue(null));

        $this->configProvider->expects($this->never())
            ->method('getDashboardConfig')
            ->will($this->returnValue($expectedConfig));

        $result = $this->dashboardFactory->createDashboardModel($dashboard);
        $this->assertEquals($expectedConfig, $result->getConfig());
        $this->assertSame($dashboard, $result->getEntity());
    }

    public function testCreateWidgetModel()
    {
        $expectedConfig = array('label' => 'test label');

        $name   = 'test';
        $widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $widget->expects($this->once())->method('getName')->will($this->returnValue($name));

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($name)
            ->will($this->returnValue($expectedConfig));

        $widgetState = $this->getMock('Oro\Bundle\DashboardBundle\Entity\WidgetState');
        $this->stateManager
            ->expects($this->once())
            ->method('getWidgetState')
            ->with($widget)
            ->will($this->returnValue($widgetState));

        $result = $this->dashboardFactory->createWidgetModel($widget);
        $this->assertEquals($expectedConfig, $result->getConfig());
        $this->assertSame($widget, $result->getEntity());
    }

    /**
     * @dataProvider createVisibleWidgetModelProvider
     */
    public function testCreateVisibleWidgetModel($widgetName, array $widgetConfig)
    {
        $widget = (new Widget())
            ->setName($widgetName);

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($widget->getName())
            ->will($this->returnValue($widgetConfig));

        $this->stateManager
            ->expects($this->once())
            ->method('getWidgetState')
            ->with($widget)
            ->will($this->returnValue($this->getMock('Oro\Bundle\DashboardBundle\Entity\WidgetState')));

        $result = $this->dashboardFactory->createVisibleWidgetModel($widget);
        $this->assertNotNull($result);
        $this->assertEquals($widgetConfig, $result->getConfig());
        $this->assertSame($widget, $result->getEntity());
    }

    public function createVisibleWidgetModelProvider()
    {
        return [
            'widget without applicable config' => [
                'widget-name',
                [
                    'label' => 'Widget label',
                ],
            ],
            'applicable widget' => [
                'widget-name',
                [
                    'label'      => 'Widget label',
                    'applicable' => 'applicable',
                ],
            ],
        ];
    }

    /**
     * @dataProvider createVisibleWidgetModelProviderInapplicable
     */
    public function testCreateVisibleWidgetModelInapplicable($widgetName, array $widgetConfig)
    {
        $widget = (new Widget())
            ->setName($widgetName);

        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($widget->getName())
            ->will($this->returnValue($widgetConfig));

        $this->stateManager
            ->expects($this->once())
            ->method('getWidgetState')
            ->with($widget)
            ->will($this->returnValue($this->getMock('Oro\Bundle\DashboardBundle\Entity\WidgetState')));

        $result = $this->dashboardFactory->createVisibleWidgetModel($widget);
        $this->assertNull($result);
    }

    public function createVisibleWidgetModelProviderInapplicable()
    {
        return [
            'inapplicable widget' => [
                'widget-name',
                [
                    'label'      => 'Widget label',
                    'applicable' => 'inapplicable',
                ],
            ],
        ];
    }
}
