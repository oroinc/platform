<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * Converted entity to the event data (array) applicable for the webhook notification.
 */
interface WebhookEventDataProviderInterface
{
    public function getEventData(string $entityClass, int|string $entityId): array;
}
