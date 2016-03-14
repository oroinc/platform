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
    const HANDLE_BEFORE = 'oro_action.action.handle_before';

    /**
     * This event occurs after execute action
     *
     * The event listener method receives Oro\Component\Action\Event\ExecuteActionEvent instance.
     *
     * @var string
     */
    const HANDLE_AFTER = 'oro_action.action.handle_after';

    /**
     * @deprecated since 1.10. Use {@see Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_BEFORE} instead
     *
     * @var string
     */
    const DEPRECATED_HANDLE_BEFORE = 'oro_workflow.action.handle_before';

    /**
     * @deprecated since 1.10. Use {@see Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_AFTER} instead
     *
     * @var string
     */
    const DEPRECATED_HANDLE_AFTER = 'oro_workflow.action.handle_after';
}
