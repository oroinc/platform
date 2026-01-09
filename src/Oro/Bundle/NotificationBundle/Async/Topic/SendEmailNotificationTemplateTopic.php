<?php

namespace Oro\Bundle\NotificationBundle\Async\Topic;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validation;

/**
 * Email notification with template to be sent.
 */
class SendEmailNotificationTemplateTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.notification.send_notification_email_template';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Email notification with template to be sent';
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
                'recipientUserId',
                'template',
            ])
            ->setDefaults([
                'templateParams' => [],
                'templateEntity' => null,
            ])
            ->addAllowedTypes('recipientUserId', 'int')
            ->addAllowedTypes('template', 'string')
            ->addAllowedTypes('templateEntity', ['string', 'null'])
            ->addAllowedTypes('templateParams', 'array')
            ->addAllowedTypes('from', 'string')
            ->addAllowedValues('from', $emailValidationCallable);
    }
}
