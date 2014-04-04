<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;

class DashboardTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Dashboard
     */
    protected $dashboard;

    protected function setUp()
    {
        $this->dashboard = new Dashboard();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('Doctrine\\Common\\Collections\\Collection', $this->dashboard->getWidgets());
    }

    public function testId()
    {
        $this->assertNull($this->dashboard->getId());
    }

    public function testLabel()
    {
        $this->assertNull($this->dashboard->getLabel());
        $value = 'test';
        $this->assertEquals($this->dashboard, $this->dashboard->setLabel($value));
        $this->assertEquals($value, $this->dashboard->getLabel());
    }

    public function testName()
    {
        $this->assertNull($this->dashboard->getName());
        $value = 'test';
        $this->assertEquals($this->dashboard, $this->dashboard->setName($value));
        $this->assertEquals($value, $this->dashboard->getName());
    }

    public function testOwner()
    {
        $this->assertNull($this->dashboard->getOwner());
        $value = $this->getMock('Oro\\Bundle\\UserBundle\\Entity\\User');
        $this->assertEquals($this->dashboard, $this->dashboard->setOwner($value));
        $this->assertEquals($value, $this->dashboard->getOwner());
    }

    public function testAddWidgets()
    {
        $widget = $this->getMock('Oro\\Bundle\\DashboardBundle\\Entity\\DashboardWidget');
        $widget->expects($this->once())->method('setDashboard')
            ->with($this->dashboard);
        $this->assertFalse($this->dashboard->getWidgets()->contains($widget));
        $this->assertEquals($this->dashboard, $this->dashboard->addWidget($widget));
        $this->assertEquals(1, $this->dashboard->getWidgets()->count());
        $this->assertTrue($this->dashboard->getWidgets()->contains($widget));
    }

    public function testHasWidget()
    {
        $widget = $this->getMock('Oro\\Bundle\\DashboardBundle\\Entity\\DashboardWidget');
        $this->assertFalse($this->dashboard->hasWidget($widget));
        $this->dashboard->addWidget($widget);
        $this->assertTrue($this->dashboard->hasWidget($widget));
    }

    public function testRemoveWidget()
    {
        $widget = $this->getMock('Oro\\Bundle\\DashboardBundle\\Entity\\DashboardWidget');
        $this->assertFalse($this->dashboard->removeWidget($widget));
        $this->dashboard->addWidget($widget);
        $this->assertTrue($this->dashboard->hasWidget($widget));
        $this->assertTrue($this->dashboard->removeWidget($widget));
        $this->assertFalse($this->dashboard->hasWidget($widget));
    }
}
