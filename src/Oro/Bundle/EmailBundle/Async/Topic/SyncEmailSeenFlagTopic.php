<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Synchronize email seen flag.
 */
class SyncEmailSeenFlagTopic extends AbstractTopic
{
    public const NAME = 'oro.email.sync_email_seen_flag';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Synchronize email seen flag';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'id',
                'seen',
            ])
            ->addAllowedTypes('id', ['string', 'int'])
            ->addAllowedTypes('seen', ['int', 'bool']);
    }
}
