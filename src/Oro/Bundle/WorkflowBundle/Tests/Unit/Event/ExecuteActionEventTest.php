<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvent;

class ExecuteActionEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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

    public function setUp()
    {
        $this->action = $this->getMockBuilder('Oro\Component\ConfigExpression\Action\ActionInterface')
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
