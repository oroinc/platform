<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Update associations to emails.
 */
class UpdateEmailAssociationsTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.email.update_associations_to_emails';
    }

    public static function getDescription(): string
    {
        return 'Update associations to emails';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }

    public function createJobName($messageBody): string
    {
        return 'oro.email.update_associations_to_emails';
    }
}
