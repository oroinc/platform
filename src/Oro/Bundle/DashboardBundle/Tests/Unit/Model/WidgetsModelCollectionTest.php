<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetsModelCollection;

class WidgetsModelCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WidgetsModelCollection
     */
    protected $collection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dashboard;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $widgetFactory;

    public function setUp()
    {
        $this->dashboard = $this->getMock('Oro\Bundle\DashboardBundle\Entity\Dashboard');
        $this->widgetFactory = $this->getMockBuilder('\Oro\Bundle\DashboardBundle\Model\WidgetModelFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = new WidgetsModelCollection($this->dashboard, $this->widgetFactory);
    }

    public function testIterated()
    {
        $expected = array(new \StdClass(), new \StdClass());
        $iteration = 0;

        $this->widgetFactory->expects($this->once())->method('getModels')->will($this->returnValue($expected));

        foreach ($this->collection as $widgetModel) {
            $this->assertSame($expected[$iteration++], $widgetModel);
        }
    }

    public function testCount()
    {
        $expected = array(new \StdClass(), new \StdClass());
        $this->widgetFactory->expects($this->once())->method('getModels')->will($this->returnValue($expected));

        $this->assertCount(count($expected), $this->collection);
    }
}
