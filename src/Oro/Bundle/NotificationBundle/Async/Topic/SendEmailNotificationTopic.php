<?php

namespace Oro\Bundle\NotificationBundle\Async\Topic;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validation;

/**
 * Email notification to be sent.
 */
class SendEmailNotificationTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.notification.send_notification_email';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Email notification to be sent';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $emailIsValidCallable = Validation::createIsValidCallable(new EmailConstraint());
        $emailValidationCallable = static function ($value) use ($emailIsValidCallable) {
            $emailAddressHelper = new EmailAddressHelper();
            // This prevents email validation errors due to formatting (e.g., '"John Doe" <john@example.com>')
            $email = $emailAddressHelper->extractPureEmailAddress($value);

            return $email && $emailIsValidCallable($email);
        };

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
            ->addAllowedValues('from', $emailValidationCallable)
            ->addAllowedValues('toEmail', $emailValidationCallable);
    }
}
