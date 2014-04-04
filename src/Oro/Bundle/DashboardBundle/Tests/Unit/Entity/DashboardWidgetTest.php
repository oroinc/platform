<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;

class DashboardWidgetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DashboardWidget
     */
    protected $widget;

    protected function setUp()
    {
        $this->widget = new DashboardWidget();
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

    public function testPosition()
    {
        $this->assertNull($this->widget->getPosition());
        $value = 100;
        $this->assertEquals($this->widget, $this->widget->setPosition($value));
        $this->assertEquals($value, $this->widget->getPosition());
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
