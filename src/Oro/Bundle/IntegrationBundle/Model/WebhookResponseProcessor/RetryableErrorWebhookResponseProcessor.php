<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Exception\RetryableWebhookDeliveryException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The RetryableErrorWebhookResponseProcessor class handles the processing of webhook responses
 * where retryable error status codes are encountered. It decides whether a response
 * is retryable based on the provided retry strategy and processes accordingly.
 */
class RetryableErrorWebhookResponseProcessor implements WebhookResponseProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private HttpClientInterface $httpClient,
        private RetryStrategyInterface $retryStrategy
    ) {
    }

    public function process(
        ResponseInterface $response,
        WebhookProducerSettings $webhook,
        string $messageId,
        bool $throwExceptionOnError = false
    ): bool {
        $statusCode = $response->getStatusCode();
        $context = $this->prepareRetryStrategyContext($response);

        // Logged as warning because etryable status indicates that something is wrong with the receiver
        // but we can try again.
        $this->logger?->warning(
            'Webhook notification endpoint returned retryable status code',
            [
                'webhook_id' => $webhook->getId(),
                'url' => $webhook->getNotificationUrl(),
                'status_code' => $statusCode,
                'response_body' => $response->getContent(false),
                'message_id' => $messageId
            ]
        );

        if ($throwExceptionOnError) {
            $redeliveryException = new RetryableWebhookDeliveryException(
                'Webhook notification endpoint returned retryable status code',
                $statusCode
            );
            // delay is the time to wait in milliseconds
            // taken either from the Retry-After header or from the retry strategy
            $delay = $this->getDelayFromHeader($response->getHeaders(false))
                ?? $this->retryStrategy->getDelay($context, null, null);

            $redeliveryException->setDelay($delay);

            throw $redeliveryException;
        }

        return false;
    }

    public function supports(ResponseInterface $response, WebhookProducerSettings $webhook): bool
    {
        $context = $this->prepareRetryStrategyContext($response);

        return $this->retryStrategy->shouldRetry($context, null, null);
    }

    /**
     * Prepare AsyncContext for retry strategy.
     */
    private function prepareRetryStrategyContext(ResponseInterface $response): AsyncContext
    {
        $callback = null;
        $info = $response->getInfo();

        return new AsyncContext($callback, $this->httpClient, $response, $info, $response->getContent(false), 0);
    }

    /**
     * This private method is taken from Symfony\Component\HttpClient\RetryableHttpClient
     */
    private function getDelayFromHeader(array $headers): ?int
    {
        if (null !== $after = $headers['retry-after'][0] ?? null) {
            if (is_numeric($after)) {
                return (int) ($after * 1000);
            }

            if (false !== $time = strtotime($after)) {
                return max(0, $time - time()) * 1000;
            }
        }

        return null;
    }
}
