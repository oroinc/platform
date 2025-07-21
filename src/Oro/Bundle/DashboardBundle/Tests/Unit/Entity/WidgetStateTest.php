<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Entity\WidgetState;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class WidgetStateTest extends TestCase
{
    private WidgetState $state;

    #[\Override]
    protected function setUp(): void
    {
        $this->state = new WidgetState();
    }

    public function testId(): void
    {
        $this->assertNull($this->state->getId());
    }

    public function testOwner(): void
    {
        $this->assertNull($this->state->getOwner());
        $owner = $this->createMock(User::class);
        $this->assertEquals($this->state, $this->state->setOwner($owner));
        $this->assertEquals($owner, $this->state->getOwner());
    }

    public function testWidget(): void
    {
        $widget = $this->createMock(Widget::class);
        $this->assertNull($this->state->getWidget());
        $this->assertEquals($this->state, $this->state->setWidget($widget));
        $this->assertEquals($widget, $this->state->getWidget());
    }

    public function testExpanded(): void
    {
        $this->assertTrue($this->state->isExpanded());
        $this->assertEquals($this->state, $this->state->setExpanded(false));
        $this->assertEquals(false, $this->state->isExpanded());
    }
}
