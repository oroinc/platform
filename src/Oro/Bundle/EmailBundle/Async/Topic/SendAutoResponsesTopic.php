<?php

namespace Oro\Bundle\EmailBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Send auto response for multiple emails.
 */
class SendAutoResponsesTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.email.send_auto_responses';
    }

    public static function getDescription(): string
    {
        return 'Send auto response for multiple emails';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['ids'])
            ->addAllowedTypes('ids', ['string[]', 'int[]']);
    }

    public function createJobName($messageBody): string
    {
        asort($messageBody['ids']);

        return sprintf(
            '%s:%s',
            'oro.email.send_auto_responses',
            md5(implode(',', $messageBody['ids']))
        );
    }
}
