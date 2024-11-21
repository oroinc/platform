<?php

namespace Oro\Bundle\ActionBundle\Event;

/**
 * Action group event that is triggered after the execution.
 */
final class ActionGroupExecuteEvent extends ActionGroupEvent
{
    public function getName(): string
    {
        return 'execute';
    }
}
