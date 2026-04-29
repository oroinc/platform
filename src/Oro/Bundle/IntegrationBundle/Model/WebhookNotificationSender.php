<?php

namespace Oro\Bundle\IntegrationBundle\Model;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\WebhookRequestContext;
use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\WebhookRequestProcessorInterface;
use Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor\WebhookResponseProcessorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends webhook notifications to a remote endpoint with retry mechanism and signature generation.
 * Processes a single webhook notification endpoint.
 */
class WebhookNotificationSender implements WebhookNotificationSenderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const string SIGNATURE_ALGORITHM = 'sha256';

    public function __construct(
        private HttpClientInterface $httpClient,
        private WebhookRequestProcessorInterface $requestProcessor,
        private WebhookResponseProcessorInterface $responseProcessor
    ) {
    }

    public function send(
        WebhookProducerSettings $webhook,
        array $eventData,
        int $timestamp,
        string $messageId,
        array $metadata = [],
        bool $throwExceptionOnError = false
    ): bool {
        $payload = [
            'topic' => $webhook->getTopic(),
            'eventData' => $eventData,
            'timestamp' => $timestamp,
            'messageId' => $messageId
        ];

        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'Webhook-Topic' => $webhook->getTopic(),
            'Webhook-Id' => $messageId
        ];
        $defaultRequestOptions = [
            'verify_peer' => $webhook->isVerifySsl(),
            'verify_host' => $webhook->isVerifySsl(),
            // Skip redirects following.
            // It causes an unnecessary load on both the sender and the receiver,
            // it's therefore recommended to update the webhook URL instead.
            'max_redirects' => 0
        ];

        try {
            $requestContext = new WebhookRequestContext(
                $payload,
                'POST',
                $defaultHeaders,
                $defaultRequestOptions,
                $metadata
            );
            $this->requestProcessor->process($requestContext, $webhook, $messageId, $throwExceptionOnError);

            $response = $this->httpClient->request(
                $requestContext->getHttpMethod(),
                $webhook->getNotificationUrl(),
                $this->getRequestOptions($requestContext, $webhook)
            );

            return $this->responseProcessor->process($response, $webhook, $messageId, $throwExceptionOnError);
        } catch (\Throwable $e) {
            $this->logger?->error(
                'Failed to send webhook notification',
                [
                    'webhook_id' => $webhook->getId(),
                    'url' => $webhook->getNotificationUrl(),
                    'topic' => $webhook->getTopic(),
                    'error' => $e->getMessage(),
                    'message_id' => $messageId
                ]
            );

            if ($throwExceptionOnError) {
                throw $e;
            }

            return false;
        }
    }

    private function getRequestOptions(
        WebhookRequestContext $requestContext,
        WebhookProducerSettings $webhook
    ): array {
        $jsonPayload = \json_encode($requestContext->getPayload(), JSON_THROW_ON_ERROR);

        $headers = $requestContext->getHeaders();
        $this->addSignature($jsonPayload, $headers, $webhook);

        $requestOptions = $requestContext->getRequestOptions();
        $requestOptions['headers'] = $headers;
        $requestOptions['body'] = $jsonPayload;

        return $requestOptions;
    }

    private function addSignature(string $payload, array &$headers, WebhookProducerSettings $webhook): void
    {
        $secret = $webhook->getSecret();
        if (!$secret) {
            return;
        }

        $headers['Webhook-Signature'] = hash_hmac(self::SIGNATURE_ALGORITHM, $payload, $secret);
        $headers['Webhook-Signature-Algorithm'] = strtoupper('HMAC-' . self::SIGNATURE_ALGORITHM);
    }
}
