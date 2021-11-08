<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\UserBundle\Entity\User;

class WidgetStateTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetState */
    private $state;

    protected function setUp(): void
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
        $owner = $this->createMock(User::class);
        $this->assertEquals($this->state, $this->state->setOwner($owner));
        $this->assertEquals($owner, $this->state->getOwner());
    }

    public function testWidget()
    {
        $widget = $this->createMock(Widget::class);
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
