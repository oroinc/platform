<?php

namespace Oro\Bundle\ActionBundle\Event;

/**
 * Action group event that is triggered before the execution.
 */
final class ActionGroupPreExecuteEvent extends ActionGroupEvent
{
    public function getName(): string
    {
        return 'pre_execute';
    }
}
