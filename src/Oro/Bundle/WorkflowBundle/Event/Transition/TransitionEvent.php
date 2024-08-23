<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\WorkflowItemAwareEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;

/**
 * Transition-related workflow event.
 */
class TransitionEvent extends WorkflowItemAwareEvent
{
    public function __construct(
        WorkflowItem $workflowItem,
        private Transition $transition
    ) {
        parent::__construct($workflowItem);
    }

    public function getTransition(): Transition
    {
        return $this->transition;
    }
}
