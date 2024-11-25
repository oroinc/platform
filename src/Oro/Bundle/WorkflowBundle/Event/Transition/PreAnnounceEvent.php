<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Triggered to validate whether the transition button is allowed.
 * Triggered before other validation logic.
 */
final class PreAnnounceEvent extends AllowanceEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'pre_announce';
    }
}
