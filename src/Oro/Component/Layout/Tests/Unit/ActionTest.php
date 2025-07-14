<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Action;
use PHPUnit\Framework\TestCase;

class ActionTest extends TestCase
{
    private Action $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new Action('test_action', ['arg1']);
    }

    public function testGetName(): void
    {
        $this->assertEquals('test_action', $this->action->getName());
    }

    public function testGetArguments(): void
    {
        $this->assertEquals(['arg1'], $this->action->getArguments());
    }

    public function testGetArgument(): void
    {
        $this->assertEquals('arg1', $this->action->getArgument(0));
    }

    public function testSetArgument(): void
    {
        $this->action->setArgument(0, 'updated');
        $this->assertEquals('updated', $this->action->getArgument(0));
    }
}
