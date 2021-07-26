<?php

namespace Oro\Component\Action\Tests\Unit\Event;

use Oro\Component\Action\Event\ExecuteActionEvent;

class ExecuteActionEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $action;

    /**
     * @var \stdClass
     */
    protected $context;

    /**
     * @var ExecuteActionEvent
     */
    protected $event;

    protected function setUp(): void
    {
        $this->action = $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
            ->getMock();

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
