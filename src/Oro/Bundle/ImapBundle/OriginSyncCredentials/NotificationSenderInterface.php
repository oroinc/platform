<?php

namespace Oro\Bundle\ImapBundle\OriginSyncCredentials;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

/**
 * Wrong credential sync email box notification sender channel.
 */
interface NotificationSenderInterface
{
    /**
     * Sends notification message about given wrong email origin.
     */
    public function sendNotification(UserEmailOrigin $emailOrigin);
}
