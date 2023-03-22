<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Update visibilities for emails and email addresses for all organizations.
 */
class UpdateVisibilitiesTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.email.update_visibilities';
    }

    public static function getDescription(): string
    {
        return 'Update visibilities for emails and email addresses for all organizations';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }

    public function createJobName($messageBody): string
    {
        return 'oro:email:update-visibilities:email-addresses';
    }
}
