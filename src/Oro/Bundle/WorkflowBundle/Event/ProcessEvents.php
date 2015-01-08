<?php

namespace Oro\Bundle\WorkflowBundle\Event;

/**
 * Contains all events thrown in the OroWorkflowBundle related to processes.
 */
final class ProcessEvents
{
    /**
     * This event occurs before process trigger handled by ProcessHandler
     *
     * The event listener method receives Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent instance.
     *
     * @var string
     */
    const HANDLE_BEFORE = 'oro_workflow.process.handle_before';

    /**
     * This event occurs after process trigger was handled by ProcessHandler
     *
     * The event listener method receives Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent instance.
     *
     * @var string
     */
    const HANDLE_AFTER = 'oro_workflow.process.handle_after';

    /**
     * This event occurs after process was handled by ProcessHandler and after all changes were flushed to persistence.
     *
     * The event listener method receives Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent instance.
     *
     * @var string
     */
    const HANDLE_AFTER_FLUSH = 'oro_workflow.process.handle_after_flush';
}
