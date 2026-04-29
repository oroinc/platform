<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;

/**
 * Removes unnecessary data from the webhook request payload in JSON:API data format.
 */
class ThinPayloadWebhookRequestProcessor implements WebhookRequestProcessorInterface
{
    public function process(
        WebhookRequestContext $context,
        WebhookProducerSettings $webhook,
        string $messageId,
        bool $throwExceptionOnError = false
    ): void {
        $payload = $context->getPayload();
        unset(
            $payload['eventData']['included'],
            $payload['eventData']['data']['attributes'],
            $payload['eventData']['data']['relationships']
        );

        $context->setPayload($payload);
    }
}
