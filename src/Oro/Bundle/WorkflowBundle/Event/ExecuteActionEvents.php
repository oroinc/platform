<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Component\Action\Event\ExecuteActionEvents as BaseExecuteActionEvents;

/**
 * @deprecated since 1.10. Use {@see Oro\Component\Action\Event\ExecuteActionEvents} instead
 */
class ExecuteActionEvents
{
    /**
     * @deprecated since 1.10. Use {@see Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_BEFORE} instead
     *
     * This event occurs before execute action
     *
     * The event listener method receives Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvent instance.
     *
     * @var string
     */
    const HANDLE_BEFORE = BaseExecuteActionEvents::DEPRECATED_HANDLE_BEFORE;

    /**
     * @deprecated since 1.10. Use {@see Oro\Component\Action\Event\ExecuteActionEvents::HANDLE_AFTER} instead
     *
     * This event occurs after execute action
     *
     * The event listener method receives Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvent instance.
     *
     * @var string
     */
    const HANDLE_AFTER = BaseExecuteActionEvents::DEPRECATED_HANDLE_AFTER;
}
