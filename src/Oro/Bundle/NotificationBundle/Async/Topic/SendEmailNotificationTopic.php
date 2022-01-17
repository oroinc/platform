<?php

namespace Oro\Bundle\NotificationBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validation;

/**
 * Email notification to be sent.
 */
class SendEmailNotificationTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.notification.send_notification_email';
    }

    public static function getDescription(): string
    {
        return 'Email notification to be sent';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $emailIsValidCallable = Validation::createIsValidCallable(new EmailConstraint());

        $resolver
            ->setRequired([
                'from',
                'toEmail',
                'subject',
                'body',
            ])
            ->setDefault('contentType', 'text/plain')
            ->addAllowedTypes('from', 'string')
            ->addAllowedTypes('toEmail', 'string')
            ->addAllowedTypes('subject', 'string')
            ->addAllowedTypes('body', 'string')
            ->addAllowedTypes('contentType', 'string')
            ->addAllowedValues('from', $emailIsValidCallable)
            ->addAllowedValues('toEmail', $emailIsValidCallable);
    }
}
