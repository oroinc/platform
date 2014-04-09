<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\DashboardBundle\Manager;

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
    protected $widgetModelFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $aclHelper;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->setMethods(array('isGranted'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->widgetModelFactory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModelFactory')
            ->disableOriginalConstructor()
            ->getMock();


        $this->manager = new Manager(
            $this->configProvider,
            $this->securityFacade,
            $this->entityManager,
            $this->widgetModelFactory,
            $this->aclHelper
        );
    }

    public function testGetDefaultDashboardName()
    {
        $expected = 'expected_dashboard';
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with('default_dashboard')
            ->will($this->returnValue($expected));

        $actual = $this->manager->getDefaultDashboardName();

        $this->assertEquals($expected, $actual);
    }

    public function testGetDashboardModel()
    {
        $firstDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $secondDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $expectedConfig = array('label' => 'test label');
        $this->securityFacade->expects($this->at(0))
            ->method('isGranted')
            ->will($this->returnValue(false));

        $this->securityFacade->expects($this->at(1))
            ->method('isGranted')
            ->will($this->returnValue(true));

        $this->configProvider->expects($this->once())
            ->method('getDashboardConfig')
            ->will($this->returnValue($expectedConfig));
        $model = $this->manager->getDashboardModel($firstDashboard);
        $this->assertNull($model);
        $model = $this->manager->getDashboardModel($secondDashboard);
        $this->assertEquals($model->getConfig(), $expectedConfig);
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
        $expectedConfig = array('label' => 'test label');

        $firstDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $secondDashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboards = array($firstDashboard, $secondDashboard);
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
        $query = $this->getMock('StdClass', array('execute'));
        $query->expects($this->once())->method('execute')->will($this->returnValue($dashboards));
        $this->aclHelper->expects($this->once())->method('apply')->with($qb)->will($this->returnValue($query));
        $repository->expects($this->once())->method('createQueryBuilder')->will($this->returnValue($qb));

        $this->securityFacade->expects($this->at(0))
            ->method('isGranted')
            ->with('VIEW', $firstDashboard)
            ->will($this->returnValue(false));
        $this->securityFacade->expects($this->at(1))
            ->method('isGranted')
            ->with('VIEW', $secondDashboard)
            ->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getDashboardConfig')
            ->will($this->returnValue($expectedConfig));

        $this->entityManager->expects($this->once())->method('getRepository')->will($this->returnValue($repository));

        $dashboards = $this->manager->getDashboards();

        $this->assertCount(1, $dashboards);
        $this->assertEquals($expectedConfig, $dashboards->current()->getConfig());
        $this->assertSame($secondDashboard, $dashboards->current()->getDashboard());
    }

    public function testSaveWidget()
    {
        $widgetId = 42;
        $expectedPosition = 34;
        $expectedExpanded = true;
        $widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\DashboardWidget');
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $repository->expects($this->at(1))->method('find')->will($this->returnValue($widget));
        $this->securityFacade->expects($this->once())->method('isGranted')->will($this->returnValue(true));
        $this->assertFalse($this->manager->saveWidget(1, array()));

        $widget->expects($this->once())->method('setPosition')->with($this->equalTo($expectedPosition));
        $widget->expects($this->once())->method('setExpanded')->with($this->equalTo($expectedExpanded));

        $this->assertTrue(
            $this->manager->saveWidget(
                $widgetId,
                array('position' => $expectedPosition, 'expanded' => $expectedExpanded)
            )
        );
    }
}
