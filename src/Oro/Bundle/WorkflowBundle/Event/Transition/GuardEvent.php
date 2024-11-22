<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Triggered to validate whether the transition execution is allowed
 */
final class GuardEvent extends AllowanceEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'guard';
    }
}
