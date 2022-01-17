<?php

namespace Oro\Bundle\ImapBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Synchronize single email via IMAP.
 */
class SyncEmailTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.imap.sync_email';
    }

    public static function getDescription(): string
    {
        return 'Synchronize single email via IMAP';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['id'])
            ->addAllowedTypes('id', ['string', 'int']);
    }
}
