<?php

namespace Oro\Bundle\ActionBundle\Event;

/**
 * Common Operation guard event used to disallow availability/execution.
 */
abstract class OperationAllowanceEvent extends OperationEvent
{
    private bool $allowed = true;

    public function setAllowed(bool $allowed): void
    {
        $this->allowed = $allowed;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }
}
