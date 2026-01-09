<?php

namespace Oro\Bundle\WorkflowBundle\Event;

/**
 * Defines event names for workflow start transition lifecycle events.
 *
 * This class provides constants for events that are dispatched during the start transition
 * process, allowing listeners to hook into specific points of the workflow initialization.
 */
class StartTransitionEvents
{
    /**
     * This event occurs before render new transition template
     *
     * The event listener method receives Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent instance.
     *
     * @var string
     */
    public const HANDLE_BEFORE_RENDER = 'oro_workflow.start_transition.handle_before_render';
}
