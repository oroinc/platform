<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\WidgetState;

class WidgetStateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WidgetState
     */
    protected $state;

    protected function setUp()
    {
        $this->state = new WidgetState();
    }

    public function testId()
    {
        $this->assertNull($this->state->getId());
    }

    public function testOwner()
    {
        $this->assertNull($this->state->getOwner());
        $owner = $this->getMock('Oro\\Bundle\\UserBundle\\Entity\\User');
        $this->assertEquals($this->state, $this->state->setOwner($owner));
        $this->assertEquals($owner, $this->state->getOwner());
    }

    public function testWidget()
    {
        $widget = $this->getMock('Oro\\Bundle\\DashboardBundle\\Entity\\Widget');
        $this->assertNull($this->state->getWidget());
        $this->assertEquals($this->state, $this->state->setWidget($widget));
        $this->assertEquals($widget, $this->state->getWidget());
    }

    public function testExpanded()
    {
        $this->assertTrue($this->state->isExpanded());
        $this->assertEquals($this->state, $this->state->setExpanded(false));
        $this->assertEquals(false, $this->state->isExpanded());
    }
}
