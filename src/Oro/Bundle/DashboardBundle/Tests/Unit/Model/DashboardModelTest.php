<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\DashboardModel;

class DashboardModelTest extends \PHPUnit_Framework_TestCase
{
    public function testAccessToInternalProperties()
    {
        $configLabel = 'sample';
        $expectedConfig = array('label' => $configLabel);
        $entityLabel = 'from_entity';

        $widgetsCollection = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetCollection')
            ->disableOriginalConstructor()
            ->getMock();
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $dashboard->expects($this->at(0))->method('getLabel')->will($this->returnValue(''));
        $dashboard->expects($this->at(1))->method('getLabel')->will($this->returnValue($entityLabel));
        $model = new DashboardModel($dashboard, $widgetsCollection, $expectedConfig);

        $this->assertEquals($model->getConfig(), $expectedConfig);
        $this->assertSame($model->getEntity(), $dashboard);
        $this->assertSame($model->getWidgets(), $widgetsCollection);
        $this->assertEquals($model->getLabel(), $configLabel);
        $this->assertEquals($model->getLabel(), $entityLabel);
    }
}
