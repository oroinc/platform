<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Workflow event that is triggered before the workflow leaves the step.
 */
final class StepLeaveEvent extends TransitionAwareEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'leave';
    }
}
