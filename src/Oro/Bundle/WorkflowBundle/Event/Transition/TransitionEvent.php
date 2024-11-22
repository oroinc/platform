<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Workflow event that is triggered before the transition execution.
 */
final class TransitionEvent extends TransitionAwareEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'transition';
    }
}
