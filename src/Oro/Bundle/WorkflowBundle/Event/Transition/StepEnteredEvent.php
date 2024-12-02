<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Workflow event that is triggered when workflow entered the step.
 */
final class StepEnteredEvent extends TransitionAwareEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'entered';
    }
}
