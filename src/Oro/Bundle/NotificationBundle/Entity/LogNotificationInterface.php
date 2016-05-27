<?php

namespace Oro\Bundle\NotificationBundle\Entity;

/**
 * Provides a way to update entity data from Swift_Mime_Message
 */
interface LogNotificationInterface
{
    /**
     * Update entity data
     *
     * @param \Swift_Mime_Message $message
     * @param int $sentCount Count of sent emails with $message
     */
    public function updateFromSwiftMessage($message, $sentCount);
}
