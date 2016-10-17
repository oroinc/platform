<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowChangesEvent extends Event
{
    /** @var WorkflowDefinition */
    private $definition;

    /**
     * @var WorkflowDefinition
     */
    private $previous;

    /**
     * @param WorkflowDefinition $definition
     * @param WorkflowDefinition $previous
     */
    public function __construct(WorkflowDefinition $definition, WorkflowDefinition $previous = null)
    {
        $this->definition = $definition;
        $this->previous = $previous;
    }

    /**
     * @return WorkflowDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return WorkflowDefinition
     */
    public function getPrevious()
    {
        return $this->previous;
    }
}
