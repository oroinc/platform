<?php

namespace Oro\Bundle\NotificationBundle\Async;

/**
 * Contains message queue topic names.
 */
class Topics
{
    const SEND_NOTIFICATION_EMAIL = 'oro.notification.send_notification_email';

    const SEND_MASS_NOTIFICATION_EMAIL = 'oro.notification.send_mass_notification_email';
}
