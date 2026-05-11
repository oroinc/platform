<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Processes the successful webhook response.
 */
class SuccessWebhookResponseProcessor implements WebhookResponseProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function process(
        ResponseInterface $response,
        WebhookProducerSettings $webhook,
        string $messageId,
        bool $throwExceptionOnError = false
    ): bool {
        $this->logger?->info(
            'Webhook notification sent successfully',
            [
                'webhook_id' => $webhook->getId(),
                'url' => $webhook->getNotificationUrl(),
                'topic' => $webhook->getTopic(),
                'status_code' => $response->getStatusCode(),
                'message_id' => $messageId
            ]
        );

        return true;
    }

    public function supports(ResponseInterface $response, WebhookProducerSettings $webhook): bool
    {
        $statusCode = $response->getStatusCode();

        return $statusCode >= Response::HTTP_OK && $statusCode < Response::HTTP_MULTIPLE_CHOICES;
    }
}
