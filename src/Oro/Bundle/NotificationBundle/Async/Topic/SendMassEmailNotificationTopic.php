<?php

namespace Oro\Bundle\NotificationBundle\Async\Topic;

/**
 * Email mass notification to be sent.
 */
class SendMassEmailNotificationTopic extends SendEmailNotificationTopic
{
    public static function getName(): string
    {
        return 'oro.notification.send_mass_notification_email';
    }

    public static function getDescription(): string
    {
        return 'Email mass notification to be sent';
    }
}
