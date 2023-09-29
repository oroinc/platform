<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Contracts\EventDispatcher\Event;

class WorkflowChangesEvent extends Event
{
    /** @var WorkflowDefinition */
    private $definition;

    /** @var WorkflowDefinition */
    private $originalDefinition;

    public function __construct(WorkflowDefinition $definition, WorkflowDefinition $original = null)
    {
        $this->definition = $definition;
        $this->originalDefinition = $original;
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
    public function getOriginalDefinition()
    {
        return $this->originalDefinition;
    }
}
