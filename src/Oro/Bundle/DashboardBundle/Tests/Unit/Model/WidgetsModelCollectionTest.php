<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetCollection;

class WidgetsModelCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WidgetCollection
     */
    protected $collection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dashboard;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    public function setUp()
    {
        $this->dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $this->factory = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\Factory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = new WidgetCollection($this->dashboard, $this->factory);
    }

    public function testIterated()
    {
        $this->markTestSkipped();
        $expected = array(new \stdClass(), new \stdClass());
        $iteration = 0;

        $this->factory->expects($this->once())->method('getModels')->will($this->returnValue($expected));

        foreach ($this->collection as $widgetModel) {
            $this->assertSame($expected[$iteration++], $widgetModel);
        }
    }

    public function testCount()
    {
        $this->markTestSkipped();
        $expected = array(new \stdClass(), new \stdClass());
        $this->factory->expects($this->once())->method('getModels')->will($this->returnValue($expected));

        $this->assertCount(count($expected), $this->collection);
    }
}
