<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Processes the 410 GONE webhook response.
 * Removes the webhook from the database.
 */
class HttpGoneWebhookResponseProcessor implements WebhookResponseProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function process(
        ResponseInterface $response,
        WebhookProducerSettings $webhook,
        string $messageId,
        bool $throwExceptionOnError = false
    ): bool {
        $em = $this->registry->getManagerForClass(WebhookProducerSettings::class);
        $removedWebhookId = $webhook->getId();
        $webhookNotificationUrl = $webhook->getNotificationUrl();

        $em->remove($webhook);
        $em->flush($webhook);

        // Log with a warning level because the system state was affected and the webhook was removed.
        $this->logger?->warning(
            'Webhook was removed because the receiver returned a 410 GONE status code.',
            [
                'webhook_id' => $removedWebhookId,
                'webhook_url' => $webhookNotificationUrl,
                'message_id' => $messageId
            ]
        );

        return true;
    }

    public function supports(ResponseInterface $response, WebhookProducerSettings $webhook): bool
    {
        $statusCode = $response->getStatusCode();

        return $statusCode === Response::HTTP_GONE;
    }
}
