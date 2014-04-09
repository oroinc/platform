<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
     /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dashboardModelFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dashboardRepository;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->setMethods(array('isGranted'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboardRepository = $this->getMockBuilder(
            'Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository'
        )->disableOriginalConstructor()
         ->getMock();

        $this->dashboardModelFactory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModelFactory')
            ->disableOriginalConstructor()
            ->getMock();


        $this->manager = new Manager(
            $this->configProvider,
            $this->securityFacade,
            $this->dashboardRepository,
            $this->dashboardModelFactory
        );
    }

    public function testGetDefaultDashboardName()
    {
        $expected = 'expected_dashboard';
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('default_dashboard')
            ->will($this->returnValue($expected));

        $firstDashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $firstDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $firstDashboard->expects($this->once())->method('getName')->will($this->returnValue(1));
        $firstDashboardModel->expects($this->once())->method('getDashboard')->will($this->returnValue($firstDashboard));

        $secondDashboardModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\DashboardModel')
            ->disableOriginalConstructor()
            ->getMock();
        $secondDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $secondDashboard->expects($this->once())->method('getName')->will($this->returnValue($expected));
        $secondDashboardModel->expects($this->once())
            ->method('getDashboard')
            ->will($this->returnValue($secondDashboard));

        $available = array($firstDashboardModel, $secondDashboardModel);

        $actual = $this->manager->findDefaultDashboard($available);

        $this->assertSame($secondDashboardModel, $actual);
    }

    public function testGetWidgetAttributesForTwig()
    {
        $expectedWidgetName = 'widget_name';
        $configs = array(
            'route'=>'sample route',
            'route_parameters'=>'sample params',
            'acl'=>'view_acl',
            'items'=>array(),
            'test-param'=>'param'
        );
        $expected = array('widgetName' => $expectedWidgetName, 'widgetTestParam' => 'param');
        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->will($this->returnValue($configs));

        $actual = $this->manager->getWidgetAttributesForTwig($expectedWidgetName);
        $this->assertEquals($expected, $actual);
    }

    public function testGetWidgetItems()
    {
        $expectedWidgetName = 'widget_name';

        $expectedItem = 'expected_item';
        $expectedValue = array('label' => 'test label', 'acl' => 'valid_acl');
        $notGrantedItem = 'not_granted_item';
        $notGrantedValue = array('label' => 'not granted label', 'acl' => 'invalid_acl');
        $configs = array(
            $expectedItem => $expectedValue,
            $notGrantedItem => $notGrantedValue
        );
        unset($expectedValue['acl']);
        $expected = array($expectedItem => $expectedValue);
        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->with($expectedWidgetName)
            ->will($this->returnValue(array('items' => $configs)));

        $this->securityFacade->expects($this->exactly(2))
            ->method('isGranted')
            ->will(
                $this->returnCallback(
                    function ($parameter) use ($notGrantedValue) {
                        return $notGrantedValue['acl'] != $parameter;
                    }
                )
            );

        $actual = $this->manager->getWidgetItems($expectedWidgetName);
        $this->assertEquals($expected, $actual);
    }

    public function testGetDashboards()
    {
        $firstDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $secondDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboards = array($firstDashboard, $secondDashboard);
        $this->dashboardRepository->expects($this->once())
            ->method('getAvailableDashboards')
            ->will($this->returnValue($dashboards));
        $this->securityFacade->expects($this->at(0))
            ->method('isGranted')
            ->with('VIEW', $firstDashboard)
            ->will($this->returnValue(false));
        $this->securityFacade->expects($this->at(1))
            ->method('isGranted')
            ->with('VIEW', $secondDashboard)
            ->will($this->returnValue(true));
        $this->dashboardModelFactory->expects($this->once())
            ->method('getDashboardModel')
            ->with($secondDashboard)
            ->will($this->returnValue($secondDashboard));
        $dashboards = $this->manager->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals($secondDashboard, $dashboards[0]);
    }
}
