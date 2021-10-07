<?php

namespace Oro\Bundle\NotificationBundle\NotificationAlert;

/**
 * Represents a notification alert item that should be stored.
 */
interface NotificationAlertInterface
{
    /**
     * Returns the notification alert id.
     */
    public function getId(): string;

    /**
     * Returns a source type this notification alert was created for.
     */
    public function getSourceType(): string;

    /**
     * Returns notification alert array representation.
     */
    public function toArray(): array;
}
