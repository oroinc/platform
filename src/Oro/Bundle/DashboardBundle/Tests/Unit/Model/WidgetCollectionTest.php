<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetCollection;

class WidgetCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $dashboard;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
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
        $fooWidget = $this->createMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $fooWidgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();

        $barWidget = $this->createMock('Oro\Bundle\DashboardBundle\Entity\Widget');
        $barWidgetModel = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetModel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dashboard->expects($this->once())
            ->method('getWidgets')
            ->will($this->returnValue([$fooWidget, $barWidget]));

        $this->factory->expects($this->exactly(2))
            ->method('createVisibleWidgetModel')
            ->will(
                $this->returnValueMap(
                    [
                        [$fooWidget, $fooWidgetModel],
                        [$barWidget, $barWidgetModel],
                    ]
                )
            );

        $this->assertEquals(
            [$fooWidgetModel, $barWidgetModel],
            $this->collection->toArray()
        );
    }
}
