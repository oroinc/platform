<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\Widget;

class WidgetTest extends \PHPUnit\Framework\TestCase
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
        $value = [1, 100];
        $this->assertEquals($this->widget, $this->widget->setLayoutPosition($value));
        $this->assertEquals($value, $this->widget->getLayoutPosition());
    }

    public function testDashboard()
    {
        $dashboard = $this->createMock('Oro\\Bundle\\DashboardBundle\\Entity\\Dashboard');
        $this->assertNull($this->widget->getDashboard());
        $this->assertEquals($this->widget, $this->widget->setDashboard($dashboard));
        $this->assertEquals($dashboard, $this->widget->getDashboard());
    }

    public function testOptions()
    {
        $this->assertEquals([], $this->widget->getOptions());
        $options['foo'] = 'bar';
        $this->widget->setOptions($options);
        $this->assertSame($options, $this->widget->getOptions());
    }
}
