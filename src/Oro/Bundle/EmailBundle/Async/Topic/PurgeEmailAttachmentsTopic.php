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
    #[\Override]
    public static function getName(): string
    {
        return 'oro.email.purge_email_attachments';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Purge email attachments';
    }

    #[\Override]
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

    #[\Override]
    public function createJobName($messageBody): string
    {
        return 'oro.email.purge_email_attachments';
    }
}
