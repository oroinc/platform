<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Async\Topic;

/**
 * A topic to process the workflow transition event trigger.
 */
class WorkflowTransitionEventTriggerTopic extends AbstractWorkflowTransitionTriggerTopic
{
    public const NAME = 'oro_message_queue.transition_trigger_event_message';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Process the workflow transition event trigger.';
    }
}
