<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a workflow definition is modified.
 *
 * This event provides access to both the updated workflow definition and the original
 * definition (if available), allowing listeners to track and react to workflow changes.
 */
class WorkflowChangesEvent extends Event
{
    /** @var WorkflowDefinition */
    private $definition;

    /** @var WorkflowDefinition */
    private $originalDefinition;

    public function __construct(WorkflowDefinition $definition, ?WorkflowDefinition $original = null)
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
