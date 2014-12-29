<?php

namespace Oro\Bundle\WorkflowBundle\Event;


class StartTransitionEvents
{
    /**
     * This event occurs before render new transition template
     *
     * The event listener method receives Oro\Bundle\WorkflowBundle\Event\StartTransitionEvent instance.
     *
     * @var string
     */
    const HANDLE_BEFORE_RENDER = 'oro_workflow.start_transition.handle_before_render';
}
