<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetModelFactory;

class WidgetModelFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetModels()
    {
        $firstWidgetName = 'first_widget_name';
        $secondWidgetName = 'second_widget_name';
        $notAllowedAcl = 'invalid_acl';
        $allowedAcl = 'valid_acl';
        $secondWidgetConfig = array('acl' => $allowedAcl);
        $config = array(
            array($firstWidgetName, array('acl' => $notAllowedAcl)),
            array($secondWidgetName, $secondWidgetConfig)
        );

        $configProvider = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->exactly(2))->method('getWidgetConfig')->will($this->returnValueMap($config));
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $map = array(array($notAllowedAcl, null, false), array($allowedAcl, null, true));
        $securityFacade->expects($this->exactly(2))->method('isGranted')->will($this->returnValueMap($map));
        $factory = new WidgetModelFactory($configProvider, $securityFacade);

        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');

        $testWidget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\DashboardWidget', array('getName'));
        $testWidget->expects($this->once())->method('getName')->will($this->returnValue($firstWidgetName));
        $secondITestWidget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\DashboardWidget', array('getName'));
        $secondITestWidget->expects($this->once())->method('getName')->will($this->returnValue($secondWidgetName));
        $widgets = array($testWidget, $secondITestWidget);

        $dashboard->expects($this->once())->method('getWidgets')->will($this->returnValue($widgets));

        $models = $factory->getModels($dashboard);

        $this->assertCount(1, $models);
        $this->assertEquals($secondITestWidget, $models[0]->getWidget());
        $this->assertEquals($secondWidgetConfig, $models[0]->getConfig());
    }
}
