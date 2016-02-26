<?php

namespace Oro\Component\Action\Event;

class ExecuteActionEvents
{
    /**
     * This event occurs before execute action
     *
     * The event listener method receives Oro\Component\Action\Event\ExecuteActionEvent instance.
     *
     * @var string
     */
    const HANDLE_BEFORE = 'oro_component.action.handle_before';

    /**
     * This event occurs after execute action
     *
     * The event listener method receives Oro\Component\Action\Event\ExecuteActionEvent instance.
     *
     * @var string
     */
    const HANDLE_AFTER = 'oro_component.action.handle_before';
}
