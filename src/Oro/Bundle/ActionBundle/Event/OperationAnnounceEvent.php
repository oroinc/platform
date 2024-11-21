<?php

namespace Oro\Bundle\ActionBundle\Event;

/**
 * Triggered to validate whether the operation button is allowed.
 */
final class OperationAnnounceEvent extends OperationAllowanceEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'announce';
    }
}
