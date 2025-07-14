<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StartTransitionEventTest extends TestCase
{
    private Transition&MockObject $transition;
    private Workflow&MockObject $workflow;
    private array $routeParameters;
    private StartTransitionEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->transition = $this->createMock(Transition::class);
        $this->workflow = $this->createMock(Workflow::class);
        $this->routeParameters = [];

        $this->event = new StartTransitionEvent($this->workflow, $this->transition, $this->routeParameters);
    }

    public function testGetWorkflow(): void
    {
        $this->assertSame($this->workflow, $this->event->getWorkflow());
    }

    public function testGetTransition(): void
    {
        $this->assertSame($this->transition, $this->event->getTransition());
    }

    public function testRouteParameters(): void
    {
        $this->assertSame($this->routeParameters, $this->event->getRouteParameters());
        $newParameters = ['test' => 1];
        $this->event->setRouteParameters($newParameters);
        $this->assertEquals($newParameters, $this->event->getRouteParameters());
    }
}
