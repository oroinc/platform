<?php

namespace Oro\Component\Action\Tests\Unit\Event;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Event\ExecuteActionEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExecuteActionEventTest extends TestCase
{
    private ActionInterface&MockObject $action;
    private \stdClass $context;
    private ExecuteActionEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = $this->createMock(ActionInterface::class);
        $this->context = new \stdClass();

        $this->event = new ExecuteActionEvent($this->context, $this->action);
    }

    public function testGetContext(): void
    {
        $this->assertSame($this->context, $this->event->getContext());
    }

    public function testGetAction(): void
    {
        $this->assertSame($this->action, $this->event->getAction());
    }
}
