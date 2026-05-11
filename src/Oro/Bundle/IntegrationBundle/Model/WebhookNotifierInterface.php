<?php

namespace Oro\Bundle\IntegrationBundle\Model;

/**
 * Queues webhook notifications for async processing.
 */
interface WebhookNotifierInterface
{
    public function sendEntityEventNotification(string $topic, object $entity): void;

    public function sendNotification(string $topic, array $eventData): void;
}
