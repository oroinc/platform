<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Workflow event that is triggered when transition execution is completed.
 */
final class TransitionCompletedEvent extends TransitionAwareEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'completed';
    }
}
