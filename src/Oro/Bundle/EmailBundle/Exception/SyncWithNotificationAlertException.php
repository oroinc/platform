<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;

/**
 * The exception that can be thrown during an email synchronization exception and that have
 * generated notification alert that describe this issue.
 */
class SyncWithNotificationAlertException extends \Exception
{
    private EmailSyncNotificationAlert $notificationAlert;

    public function __construct(
        EmailSyncNotificationAlert $notificationAlert,
        string $message = '',
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->notificationAlert = $notificationAlert;
        parent::__construct($message, $code, $previous);
    }

    public function getNotificationAlert(): EmailSyncNotificationAlert
    {
        return $this->notificationAlert;
    }
}
