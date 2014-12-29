<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class StartTransitionEvent extends Event
{
    /**
     * @var Transition
     */
    protected $transition;

    /**
     * @var array
     */
    protected $routeParameters;

    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @param Workflow   $workflow
     * @param Transition $transition
     * @param array      $routeParameters
     */
    public function __construct(Workflow $workflow, Transition $transition, $routeParameters)
    {
        $this->transition = $transition;
        $this->routeParameters = $routeParameters;
        $this->workflow = $workflow;
    }

    /**
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * @param array $routeParameters
     */
    public function setRouteParameters($routeParameters)
    {
        $this->routeParameters = $routeParameters;
    }

    /**
     * @return Transition
     */
    public function getTransition()
    {
        return $this->transition;
    }

    /**
     * @return Workflow
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }
}
