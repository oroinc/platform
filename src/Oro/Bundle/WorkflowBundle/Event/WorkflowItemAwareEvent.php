<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Workflow event containing WorkflowItem entity.
 */
abstract class WorkflowItemAwareEvent extends Event
{
    public function __construct(
        private WorkflowItem $workflowItem
    ) {
    }

    abstract public function getName(): string;

    public function setWorkflowItem(WorkflowItem $workflowItem): void
    {
        $this->workflowItem = $workflowItem;
    }

    public function getWorkflowItem(): WorkflowItem
    {
        return $this->workflowItem;
    }
}
