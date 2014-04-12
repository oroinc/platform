<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\DependencyInjection\Configuration;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;
use Oro\Bundle\DashboardBundle\Model\WidgetManager;

class WidgetManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WidgetManager
     */
    protected $widgetManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widgetModelFactory;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->widgetModelFactory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModelFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->widgetManager = new WidgetManager(
            $this->configProvider,
            $this->entityManager,
            $this->securityFacade,
            $this->widgetModelFactory
        );
    }

    protected function initRepository($countOfCall = 1)
    {
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->expects($this->exactly($countOfCall))->method('getRepository')->will(
            $this->returnValue($this->repository)
        );
    }

    public function testGetAvailableWidgets()
    {
        $allowedAcl = 'allowedACl';
        $notAllowedAcl = 'notAllowedAcl';

        $expected = array(
            'firstWidget' => array('label' => 'test label'),
            'secondWidget' => array('label' => 'second test label', 'acl' => $allowedAcl)
        );

        $params  = $expected;

        $params['thirdWidget'] = array('label' => 'third test label', 'acl' => $notAllowedAcl);

        $this->configProvider->expects($this->once())->method('getWidgetConfigs')->will($this->returnValue($params));

        $map = array(array($allowedAcl, null, true), array($notAllowedAcl, null, false));
        $this->securityFacade->expects($this->exactly(2))->method('isGranted')->will($this->returnValueMap($map));


        $this->assertEquals($expected, $this->widgetManager->getAvailableWidgets());
    }

    public function testCreateWidgetReturnNullIfDashboardNotFound()
    {
        $validWidgetConfig = 'valid_widget';
        $map = array(array($validWidgetConfig, true));
        $this->initRepository(2);
        $this->configProvider->expects($this->exactly(2))->method('hasWidgetConfig')->will($this->returnValueMap($map));
        $this->assertNull($this->widgetManager->createWidget($validWidgetConfig, 1));
        $this->assertNull($this->widgetManager->createWidget('invalid widget', 1));
    }

    public function testCreateWidgetReturnNullIfAclNotGranted()
    {
        $validWidgetConfig = 'valid_widget';
        $expectAcl = 'test acl';
        $config = array('acl' => $expectAcl);
        $expectedId = 42;
        $this->initRepository();
        $this->repository->expects($this->once())
            ->method('find')
            ->with($expectedId)->will($this->returnValue(new \stdClass()));
        $this->configProvider->expects($this->once())->method('hasWidgetConfig')->will($this->returnValue(true));
        $this->configProvider->expects($this->once())->method('getWidgetConfig')->will($this->returnValue($config));
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($expectAcl)
            ->will($this->returnValue(false));
        $this->assertNull($this->widgetManager->createWidget($validWidgetConfig, $expectedId));
    }

    public function testCreateWidgetSaveAndReturnCorrectData()
    {
        $this->initRepository();
        $expectedId = 42;
        $expectedPosition = array(Configuration::FIRST_COLUMN, -1);

        $expectedWidget = new DashboardWidget();
        $widgetName = 'widget name';
        $expectedWidget->setName($widgetName);
        $widgetConfig = array();
        $widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\DashboardWidget');
        $widgets = array($widget);
        $widget->expects($this->once())
            ->method('getLayoutPosition')
            ->will($this->returnValue(array(Configuration::FIRST_COLUMN, 0)));
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboard->expects($this->once())->method('getWidgets')->will($this->returnValue($widgets));
        $expectedWidget->setDashboard($dashboard);
        $expectedWidget->setLayoutPosition($expectedPosition);
        $expectedWidget->setExpanded(true);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($expectedId)
            ->will($this->returnValue($dashboard));

        $this->configProvider->expects($this->once())->method('hasWidgetConfig')->will($this->returnValue(true));
        $this->configProvider->expects($this->once())
            ->method('getWidgetConfig')
            ->will($this->returnValue($widgetConfig));
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->equalTo($expectedWidget));
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->with($this->equalTo($expectedWidget));
        $this->widgetManager->createWidget($widgetName, $expectedId);
    }
}
