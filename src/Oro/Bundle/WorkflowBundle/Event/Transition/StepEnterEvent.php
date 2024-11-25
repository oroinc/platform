<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Workflow event that is triggered before the workflow enters the step.
 */
final class StepEnterEvent extends TransitionAwareEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'enter';
    }
}
