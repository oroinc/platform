<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;

/**
 * Processes the webhook request data model.
 * Allows modification of the request data before sending it to the remote endpoint.
 */
interface WebhookRequestProcessorInterface
{
    public function process(
        WebhookRequestContext $context,
        WebhookProducerSettings $webhook,
        string $messageId,
        bool $throwExceptionOnError = false
    ): void;
}
