<?php

namespace Oro\Bundle\ImapBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Clear inactive mailbox.
 */
class ClearInactiveMailboxTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const NAME = 'oro.imap.clear_inactive_mailbox';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Clear inactive mailbox';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined(['id'])
            ->addAllowedTypes('id', ['string', 'int']);
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        return self::getName();
    }
}
