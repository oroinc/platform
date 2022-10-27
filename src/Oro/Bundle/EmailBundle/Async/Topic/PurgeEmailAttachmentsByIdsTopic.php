<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Purge email attachments by ids.
 */
class PurgeEmailAttachmentsByIdsTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.email.purge_email_attachments_by_ids';
    }

    public static function getDescription(): string
    {
        return 'Purge email attachments by ids';
    }

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
