<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\Widget;

class WidgetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Widget
     */
    protected $widget;

    protected function setUp()
    {
        $this->widget = new Widget();
    }

    public function testId()
    {
        $this->assertNull($this->widget->getId());
    }

    public function testName()
    {
        $this->assertNull($this->widget->getName());
        $value = 'test';
        $this->assertEquals($this->widget, $this->widget->setName($value));
        $this->assertEquals($value, $this->widget->getName());
    }

    public function testLayoutPosition()
    {
        $this->assertNull($this->widget->getLayoutPosition());
        $value = array(1, 100);
        $this->assertEquals($this->widget, $this->widget->setLayoutPosition($value));
        $this->assertEquals($value, $this->widget->getLayoutPosition());
    }

    public function testExpanded()
    {
        $this->assertTrue($this->widget->isExpanded());
        $this->assertEquals($this->widget, $this->widget->setExpanded(false));
        $this->assertFalse($this->widget->isExpanded());
    }

    public function testDashboard()
    {
        $dashboard = $this->getMock('Oro\\Bundle\\DashboardBundle\\Entity\\Dashboard');
        $this->assertNull($this->widget->getDashboard());
        $this->assertEquals($this->widget, $this->widget->setDashboard($dashboard));
        $this->assertEquals($dashboard, $this->widget->getDashboard());
    }
}
