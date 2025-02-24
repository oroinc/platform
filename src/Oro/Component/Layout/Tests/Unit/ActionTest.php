<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Action;

class ActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Action */
    private $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new Action('test_action', ['arg1']);
    }

    public function testGetName()
    {
        $this->assertEquals('test_action', $this->action->getName());
    }

    public function testGetArguments()
    {
        $this->assertEquals(['arg1'], $this->action->getArguments());
    }

    public function testGetArgument()
    {
        $this->assertEquals('arg1', $this->action->getArgument(0));
    }

    public function testSetArgument()
    {
        $this->action->setArgument(0, 'updated');
        $this->assertEquals('updated', $this->action->getArgument(0));
    }
}
