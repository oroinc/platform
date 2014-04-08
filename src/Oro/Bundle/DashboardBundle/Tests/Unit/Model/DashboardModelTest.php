<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\DashboardModel;

class DashboardModelTest extends \PHPUnit_Framework_TestCase
{
    public function testAccessToInternalProperties()
    {
        $expectedConfig = array('label'=>'sample');

        $widgetsCollection = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetsModelCollection')
            ->disableOriginalConstructor()
            ->getMock();
        $dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $model = new DashboardModel($widgetsCollection, $expectedConfig, $dashboard);

        $this->assertEquals($model->getConfig(), $expectedConfig);
        $this->assertSame($model->getDashboard(), $dashboard);
        $this->assertSame($model->getWidgets(), $widgetsCollection);
    }
}
