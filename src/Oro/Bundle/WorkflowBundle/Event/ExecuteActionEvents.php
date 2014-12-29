<?php

namespace Oro\Bundle\WorkflowBundle\Event;


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
