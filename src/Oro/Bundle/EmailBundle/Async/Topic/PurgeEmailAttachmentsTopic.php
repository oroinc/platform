<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Purge email attachments.
 */
class PurgeEmailAttachmentsTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.email.purge_email_attachments';
    }

    public static function getDescription(): string
    {
        return 'Purge email attachments';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'size' => null,
                'all' => false,
            ])
            ->addAllowedTypes('all', ['int', 'bool'])
            ->addAllowedTypes('size', ['int', 'null']);
    }

    public function createJobName($messageBody): string
    {
        return 'oro.email.purge_email_attachments';
    }
}
