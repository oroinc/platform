<?php

namespace Oro\Bundle\ActionBundle\Event;

/**
 * Triggered to validate whether the operation execution is allowed
 */
final class OperationGuardEvent extends OperationAllowanceEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'guard';
    }
}
