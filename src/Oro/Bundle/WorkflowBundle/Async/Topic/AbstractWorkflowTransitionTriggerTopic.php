<?php

declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Async\Topic;

use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An abstract topic to process the workflow transition trigger.
 */
abstract class AbstractWorkflowTransitionTriggerTopic extends AbstractTopic
{
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(TransitionTriggerMessage::TRANSITION_TRIGGER)
            ->setAllowedTypes(TransitionTriggerMessage::TRANSITION_TRIGGER, 'int')
            ->setAllowedValues(TransitionTriggerMessage::TRANSITION_TRIGGER, static fn (int $value) => $value > 0);

        $resolver
            ->setRequired(TransitionTriggerMessage::MAIN_ENTITY)
            ->setAllowedTypes(TransitionTriggerMessage::MAIN_ENTITY, ['int', 'string', 'array']);
    }
}
