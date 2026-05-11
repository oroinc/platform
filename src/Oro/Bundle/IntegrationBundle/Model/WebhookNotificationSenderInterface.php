<?php

namespace Oro\Bundle\IntegrationBundle\Model;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;

/**
 * Sends webhook notifications to a remote endpoint.
 */
interface WebhookNotificationSenderInterface
{
    public function send(
        WebhookProducerSettings $webhook,
        array $eventData,
        int $timestamp,
        string $messageId,
        array $metadata = [],
        bool $throwExceptionOnError = false
    ): bool;
}
