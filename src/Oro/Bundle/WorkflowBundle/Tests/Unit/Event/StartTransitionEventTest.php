<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent;

class StartTransitionEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transition;

    /**
     * @var array
     */
    protected $routeParameters;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflow;

    /**
     * @var StartTransitionEvent
     */
    protected $event;

    public function setUp()
    {
        $this->transition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Transition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

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
