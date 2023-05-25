<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Async\Topic;

use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired(TransitionTriggerMessage::MAIN_ENTITY)
            ->setAllowedTypes(TransitionTriggerMessage::MAIN_ENTITY, ['int', 'string', 'array', 'null']);
    }
}
