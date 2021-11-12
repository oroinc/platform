<?php

namespace Oro\Component\Action\Tests\Unit\Event;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Event\ExecuteActionEvent;

class ExecuteActionEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $action;

    /** @var \stdClass */
    private $context;

    /** @var ExecuteActionEvent */
    private $event;

    protected function setUp(): void
    {
        $this->action = $this->createMock(ActionInterface::class);
        $this->context = new \stdClass();

        $this->event = new ExecuteActionEvent($this->context, $this->action);
    }

    public function testGetContext()
    {
        $this->assertSame($this->context, $this->event->getContext());
    }

    public function testGetAction()
    {
        $this->assertSame($this->action, $this->event->getAction());
    }
}
