<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Async\Topic;

/**
 * A topic to process the workflow transition cron trigger.
 */
class WorkflowTransitionCronTriggerTopic extends AbstractWorkflowTransitionTriggerTopic
{
    public const NAME = 'oro_message_queue.transition_trigger_cron_message';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Process the workflow transition cron trigger.';
    }
}
