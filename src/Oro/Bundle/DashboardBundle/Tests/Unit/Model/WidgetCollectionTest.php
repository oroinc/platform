<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetCollection;

class WidgetsModelCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dashboard;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var WidgetCollection
     */
    protected $collection;

    protected function setUp()
    {
        $this->dashboard = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Entity\Dashboard')
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\Factory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = new WidgetCollection($this->dashboard, $this->factory);
    }

    public function testInitialize()
    {
        $fooWidget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $fooWidgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();

        $barWidget = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $barWidgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboard->expects($this->once())
            ->method('getWidgets')
            ->will($this->returnValue(array($fooWidget, $barWidget)));

        $this->factory->expects($this->exactly(2))
            ->method('createWidgetModel')
            ->will(
                $this->returnValueMap(
                    array(
                        array($fooWidget, $fooWidgetModel),
                        array($barWidget, $barWidgetModel),
                    )
                )
            );

        $this->assertEquals(
            array($fooWidgetModel, $barWidgetModel),
            $this->collection->toArray()
        );
    }
}
