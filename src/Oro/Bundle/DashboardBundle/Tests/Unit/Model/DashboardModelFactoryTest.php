<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\DashboardModelFactory;

class DashboardModelFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DashboardModelFactory
     */
    protected $dashboardFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    public function setUp()
    {
        $widgetModelFactory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModelFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardFactory = new DashboardModelFactory($widgetModelFactory, $this->configProvider);
    }

    public function testGetDashboardModel()
    {
        $expectedConfig  = array('label' => 'test label');
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboard->expects($this->once())->method('getName')->will($this->returnValue('test'));
        $this->configProvider->expects($this->once())
            ->method('hasDashboardConfig')
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getDashboardConfig')
            ->will($this->returnValue($expectedConfig));

        $result = $this->dashboardFactory->getDashboardModel($dashboard);
        $this->assertEquals($expectedConfig, $result->getConfig());
        $this->assertSame($dashboard, $result->getDashboard());
    }
}
