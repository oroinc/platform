<?php

namespace Oro\Bundle\NotificationBundle\Async;

/**
 * Contains message queue topic names.
 */
class Topics
{
    public const SEND_NOTIFICATION_EMAIL = 'oro.notification.send_notification_email';
    public const SEND_MASS_NOTIFICATION_EMAIL = 'oro.notification.send_mass_notification_email';
    public const SEND_NOTIFICATION_EMAIL_TEMPLATE = 'oro.notification.send_notification_email_template';
}
