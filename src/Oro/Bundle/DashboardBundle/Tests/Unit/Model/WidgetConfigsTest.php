<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Symfony\Component\HttpFoundation\Request;

class WidgetConfigsTest extends \PHPUnit_Framework_TestCase
{
    private $dashboardManager;
    private $stateManager;

    private $widgetConfigs;

    public function setUp()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver = $this->getMock('Oro\Component\Config\Resolver\ResolverInterface');

        $this->dashboardManager = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\Manager')
                ->disableOriginalConstructor()
                ->getMock();

        $this->stateManager = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\StateManager')
                ->disableOriginalConstructor()
                ->getMock();

        $this->widgetConfigs = new WidgetConfigs($configProvider, $securityFacade, $resolver, $this->dashboardManager, $this->stateManager);
    }

    public function testGetCurrentWidgetOptionsShouldReturnEmptyArrayIfRequestIsNull()
    {
        $this->widgetConfigs->setRequest(null);

        $this->assertEmpty($this->widgetConfigs->getCurrentWidgetOptions());
    }

    public function testGetCurrentWidgetOptionsShouldReturnEmptyArrayIfThereIsNoWidgetIdInRequestQuery()
    {
        $request = new Request();
        $this->widgetConfigs->setRequest($request);

        $this->assertEmpty($this->widgetConfigs->getCurrentWidgetOptions());
    }

    public function testGetCurrentWidgetOptionsShouldReturnOptionsOfWidget()
    {
        $request = new Request([
            '_widgetId' => 1,
        ]);
        $this->widgetConfigs->setRequest($request);

        $widget = new Widget();
        $this->dashboardManager
            ->expects($this->once())
            ->method('findWidgetModel')
            ->with(1)
            ->will($this->returnValue($widget));

        $options = ['k' => 'v', 'k2' => 'v2'];
        $widgetState = new WidgetState();
        $widgetState->setOptions($options);
        $this->stateManager
            ->expects($this->once())
            ->method('getWidgetState')
            ->with($widget)
            ->will($this->returnValue($widgetState));

        $this->assertEquals($options, $this->widgetConfigs->getCurrentWidgetOptions());
    }
}
