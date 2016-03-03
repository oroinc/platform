<?php

namespace Oro\Bundle\WorkflowBundle\Event;

/**
 * @deprecated since 1.10. Use {@see Oro\Component\Action\Event\ExecuteActionEvent} instead
 */
class ExecuteActionEvents
{
    /**
     * This event occurs before execute action
     *
     * The event listener method receives Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvent instance.
     *
     * @var string
     */
    const HANDLE_BEFORE = 'oro_workflow.action.handle_before';

    /**
     * This event occurs after execute action
     *
     * The event listener method receives Oro\Bundle\WorkflowBundle\Event\ExecuteActionEvent instance.
     *
     * @var string
     */
    const HANDLE_AFTER = 'oro_workflow.action.handle_after';
}
