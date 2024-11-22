<?php

namespace Oro\Bundle\ActionBundle\Event;

/**
 * Action Group guard event used to disallow availability/execution.
 */
class ActionGroupGuardEvent extends ActionGroupEvent
{
    private bool $allowed = true;

    #[\Override]
    public function getName(): string
    {
        return 'guard';
    }

    public function setAllowed(bool $allowed): void
    {
        $this->allowed = $allowed;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }
}
