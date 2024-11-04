<?php

namespace Oro\Bundle\ImapBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Synchronize multiple emails via IMAP.
 */
class SyncEmailsTopic extends AbstractTopic
{
    public const NAME = 'oro.imap.sync_emails';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Synchronize multiple emails via IMAP';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['ids'])
            ->addAllowedTypes('ids', ['string[]', 'int[]']);
    }
}
