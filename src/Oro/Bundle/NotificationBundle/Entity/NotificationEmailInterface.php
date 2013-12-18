<?php

namespace Oro\Bundle\NotificationBundle\Entity;

/**
 * Represents an entity that may be notified by email messages
 */
interface NotificationEmailInterface
{
    /**
     * Gets an email addresses which can be used to send messages
     *
     * @return []
     */
    public function getNotificationEmails();
}
