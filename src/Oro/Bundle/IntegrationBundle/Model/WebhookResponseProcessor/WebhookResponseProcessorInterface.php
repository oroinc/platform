<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Processes the webhook response.
 */
interface WebhookResponseProcessorInterface
{
    public function process(
        ResponseInterface $response,
        WebhookProducerSettings $webhook,
        string $messageId,
        bool $throwExceptionOnError = false
    ): bool;

    public function supports(ResponseInterface $response, WebhookProducerSettings $webhook): bool;
}
