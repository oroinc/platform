<?php

namespace Oro\Component\Action\Event;

/**
 * Defines event names for action execution lifecycle.
 *
 * This class provides constants for events dispatched during action execution, allowing listeners
 * to hook into the action lifecycle at specific points. Events are dispatched with {@see ExecuteActionEvent}
 * instances containing the execution context and action details.
 */
class ExecuteActionEvents
{
    /**
     * This event occurs before execute action
     *
     * The event listener method receives Oro\Component\Action\Event\ExecuteActionEvent instance.
     *
     * @var string
     */
    public const HANDLE_BEFORE = 'oro_action.action.handle_before';

    /**
     * This event occurs after execute action
     *
     * The event listener method receives Oro\Component\Action\Event\ExecuteActionEvent instance.
     *
     * @var string
     */
    public const HANDLE_AFTER = 'oro_action.action.handle_after';
}
