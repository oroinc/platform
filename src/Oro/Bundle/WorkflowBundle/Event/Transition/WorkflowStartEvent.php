<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Workflow event that is triggered when the workflow is started.
 */
final class WorkflowStartEvent extends TransitionAwareEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'start';
    }
}
