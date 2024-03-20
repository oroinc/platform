<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Contracts\EventDispatcher\Event;

class WorkflowItemAwareEvent extends Event
{
    public function __construct(
        private WorkflowItem $workflowItem
    ) {
    }

    public function getWorkflowItem(): WorkflowItem
    {
        return $this->workflowItem;
    }
}
