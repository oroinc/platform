<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Async\Topic;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base topic for the forgot password requests.
 */
abstract class AbstractPasswordResetRequestTopic extends AbstractTopic
{
    public const string USER_IDENTIFIER = 'userIdentifier';

    #[\Override]
    public function getDefaultPriority(string $queueName): string
    {
        return MessagePriority::HIGH;
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->define(self::USER_IDENTIFIER)
            ->required()
            ->allowedTypes('string');
    }
}
