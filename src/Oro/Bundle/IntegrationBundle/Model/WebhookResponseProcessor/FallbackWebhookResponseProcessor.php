<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Exception\WebhookDeliveryException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Processes the webhook response in case if it was not processed by any other processor.
 */
class FallbackWebhookResponseProcessor implements WebhookResponseProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function process(
        ResponseInterface $response,
        WebhookProducerSettings $webhook,
        string $messageId,
        bool $throwExceptionOnError = false
    ): bool {
        $statusCode = $response->getStatusCode();

        $this->logger?->error(
            'Webhook notification endpoint returned non-success status code',
            [
                'webhook_id' => $webhook->getId(),
                'url' => $webhook->getNotificationUrl(),
                'status_code' => $statusCode,
                'response_body' => $response->getContent(false),
                'message_id' => $messageId
            ]
        );

        if ($throwExceptionOnError) {
            throw new WebhookDeliveryException(
                'Webhook notification endpoint returned non-success status code',
                $statusCode
            );
        }

        return false;
    }

    public function supports(ResponseInterface $response, WebhookProducerSettings $webhook): bool
    {
        return true;
    }
}
