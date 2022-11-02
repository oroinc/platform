<?php

namespace Oro\Bundle\NotificationBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validation;

/**
 * Email notification with template to be sent.
 */
class SendEmailNotificationTemplateTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.notification.send_notification_email_template';
    }

    public static function getDescription(): string
    {
        return 'Email notification with template to be sent';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
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
            ->addAllowedValues('from', Validation::createIsValidCallable(new EmailConstraint()));
    }
}
