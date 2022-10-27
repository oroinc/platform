<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;

/**
 * Represents a notification event handler.
 */
interface EventHandlerInterface
{
    /**
     * Handles the given notification event.
     *
     * @param NotificationEvent   $event
     * @param EmailNotification[] $matchedNotifications
     */
    public function handle(NotificationEvent $event, array $matchedNotifications);
}
