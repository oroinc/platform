<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Workflow event that is triggered when the workflow is finished.
 */
final class WorkflowFinishEvent extends TransitionAwareEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'finish';
    }
}
