<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

/**
 * Triggered to validate whether the transition button is allowed
 */
final class AnnounceEvent extends AllowanceEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'announce';
    }
}
