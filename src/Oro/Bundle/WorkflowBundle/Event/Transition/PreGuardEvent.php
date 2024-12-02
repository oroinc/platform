<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Triggered to validate whether the transition execution is allowed.
 * Triggered before other validation logic.
 */
final class PreGuardEvent extends AllowanceEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'pre_guard';
    }
}
