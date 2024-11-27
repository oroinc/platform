<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Workflow event triggered when the transition form is initialised.
 */
final class TransitionFormInitEvent extends TransitionAwareEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'transition_form_init';
    }
}
