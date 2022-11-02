<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class StartTransitionEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var Transition|\PHPUnit\Framework\MockObject\MockObject */
    private $transition;

    /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject */
    private $workflow;

    /** @var array */
    private $routeParameters;

    /** @var StartTransitionEvent */
    private $event;

    protected function setUp(): void
    {
        $this->transition = $this->createMock(Transition::class);
        $this->workflow = $this->createMock(Workflow::class);
        $this->routeParameters = [];

        $this->event = new StartTransitionEvent($this->workflow, $this->transition, $this->routeParameters);
    }

    public function testGetWorkflow()
    {
        $this->assertSame($this->workflow, $this->event->getWorkflow());
    }

    public function testGetTransition()
    {
        $this->assertSame($this->transition, $this->event->getTransition());
    }

    public function testRouteParameters()
    {
        $this->assertSame($this->routeParameters, $this->event->getRouteParameters());
        $newParameters = ['test' => 1];
        $this->event->setRouteParameters($newParameters);
        $this->assertEquals($newParameters, $this->event->getRouteParameters());
    }
}
