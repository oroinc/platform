<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetModel;

class WidgetModelTest extends \PHPUnit_Framework_TestCase
{
    public function testAccessToInternalProperties()
    {
        $expectedConfig = array('label'=>'sample');

        $widget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\DashboardWidget');
        $model = new WidgetModel($expectedConfig, $widget);

        $this->assertEquals($model->getConfig(), $expectedConfig);
        $this->assertSame($model->getWidget(), $widget);
    }
}
