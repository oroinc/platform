<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Factory
     */
    protected $dashboardFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    public function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardFactory = new Factory($this->configProvider);
    }

    public function testCreateDashboardModel()
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

        $result = $this->dashboardFactory->createDashboardModel($dashboard);
        $this->assertEquals($expectedConfig, $result->getConfig());
        $this->assertSame($dashboard, $result->getEntity());
    }
}
