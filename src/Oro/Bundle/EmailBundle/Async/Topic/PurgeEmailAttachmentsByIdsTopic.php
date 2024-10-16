<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Purge email attachments by ids.
 */
class PurgeEmailAttachmentsByIdsTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.email.purge_email_attachments_by_ids';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Purge email attachments by ids';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'jobId',
                'ids',
            ])
            ->setDefined(['size'])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('ids', ['string[]', 'int[]'])
            ->addAllowedTypes('size', 'int');
    }
}
