<?php

namespace Oro\Bundle\EmailBundle\Sync;

/**
 * The bag that stores the notification alerts that was generated during email sync.
 */
class EmailSyncNotificationBag
{
    private array $notifications = [];

    public function addNotification(EmailSyncNotificationAlert $notificationAlert): void
    {
        $this->notifications[] = $notificationAlert;
    }

    public function getNotifications(): array
    {
        return $this->notifications;
    }

    public function emptyNotifications(): void
    {
        $this->notifications = [];
    }
}
