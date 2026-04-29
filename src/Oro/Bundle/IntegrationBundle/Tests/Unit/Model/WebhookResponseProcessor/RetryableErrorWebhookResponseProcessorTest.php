<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\WebhookResponseProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Exception\RetryableWebhookDeliveryException;
use Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor\RetryableErrorWebhookResponseProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RetryableErrorWebhookResponseProcessorTest extends TestCase
{
    private RetryStrategyInterface&MockObject $retryStrategy;
    private LoggerInterface&MockObject $logger;
    private RetryableErrorWebhookResponseProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->retryStrategy = $this->createMock(RetryStrategyInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new RetryableErrorWebhookResponseProcessor(
            new MockHttpClient(),
            $this->retryStrategy
        );
        $this->processor->setLogger($this->logger);
    }

    public function testSupportsDelegatesToRetryStrategy(): void
    {
        $response = $this->createResponse('Retry Later', 429);
        $webhook = $this->createMock(WebhookProducerSettings::class);

        $this->retryStrategy->expects(self::once())
            ->method('shouldRetry')
            ->with(self::isInstanceOf(AsyncContext::class), null, null)
            ->willReturn(true);

        self::assertTrue($this->processor->supports($response, $webhook));
    }

    public function testSupportsReturnsFalseWhenRetryStrategyReturnsFalse(): void
    {
        $response = $this->createResponse('Not Retryable', 400);
        $webhook = $this->createMock(WebhookProducerSettings::class);

        $this->retryStrategy->expects(self::once())
            ->method('shouldRetry')
            ->willReturn(false);

        self::assertFalse($this->processor->supports($response, $webhook));
    }

    public function testProcessLogsWarningAndReturnsFalse(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('getId')
            ->willReturn('webhook-id');
        $webhook->expects(self::once())
            ->method('getNotificationUrl')
            ->willReturn('https://example.com');

        $response = $this->createResponse('Retry Later', 429);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Webhook notification endpoint returned retryable status code',
                self::callback(static function (array $ctx) {
                    return $ctx['webhook_id'] === 'webhook-id'
                        && $ctx['status_code'] === 429
                        && $ctx['message_id'] === 'msg-001';
                })
            );

        $result = $this->processor->process($response, $webhook, 'msg-001', false);

        self::assertFalse($result);
    }

    public function testProcessThrowsRetryableExceptionWhenFlagSet(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('getId')
            ->willReturn('id');
        $webhook->expects(self::once())
            ->method('getNotificationUrl')
            ->willReturn('https://example.com');

        $response = $this->createResponse('Service Unavailable', 503);

        $this->retryStrategy->expects(self::once())
            ->method('getDelay')
            ->with(self::isInstanceOf(AsyncContext::class), null, null)
            ->willReturn(3000);
        $this->logger->expects(self::once())
            ->method('warning');

        $this->expectException(RetryableWebhookDeliveryException::class);
        $this->expectExceptionMessage('Webhook notification endpoint returned retryable status code');
        $this->expectExceptionCode(503);

        $this->processor->process($response, $webhook, 'msg-002', true);
    }

    public function testProcessSetsDelayFromRetryStrategy(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('getId')
            ->willReturn('id');
        $webhook->expects(self::once())
            ->method('getNotificationUrl')
            ->willReturn('https://example.com');

        // No Retry-After header -> delay comes from strategy
        $response = $this->createResponse('Service Unavailable', 503);

        $this->retryStrategy->expects(self::once())
            ->method('getDelay')
            ->willReturn(2000);
        $this->logger->expects(self::once())
            ->method('warning');

        try {
            $this->processor->process($response, $webhook, 'msg-003', true);
            self::fail('Expected RetryableWebhookDeliveryException was not thrown');
        } catch (RetryableWebhookDeliveryException $e) {
            self::assertSame(2000, $e->getDelay());
        }
    }

    public function testProcessSetsDelayFromNumericRetryAfterHeader(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('getId')
            ->willReturn('id');
        $webhook->expects(self::once())
            ->method('getNotificationUrl')
            ->willReturn('https://example.com');

        // Retry-After: 5 -> 5000 ms; getDelay() must NOT be called
        $response = $this->createResponse(
            'Too Many Requests',
            429,
            ['response_headers' => ['Retry-After: 5']]
        );

        $this->retryStrategy->expects(self::never())
            ->method('getDelay');
        $this->logger->expects(self::once())
            ->method('warning');

        try {
            $this->processor->process($response, $webhook, 'msg-004', true);
            self::fail('Expected RetryableWebhookDeliveryException was not thrown');
        } catch (RetryableWebhookDeliveryException $e) {
            self::assertSame(5000, $e->getDelay());
        }
    }

    public function testProcessSetsDelayFromDecimalRetryAfterHeader(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('getId')
            ->willReturn('id');
        $webhook->expects(self::once())
            ->method('getNotificationUrl')
            ->willReturn('https://example.com');

        // Retry-After: 2.5 -> 2500 ms
        $response = $this->createResponse(
            'Too Many Requests',
            429,
            ['response_headers' => ['Retry-After: 2.5']]
        );

        $this->retryStrategy->expects(self::never())
            ->method('getDelay');
        $this->logger->expects(self::once())
            ->method('warning');

        try {
            $this->processor->process($response, $webhook, 'msg-005', true);
            self::fail('Expected RetryableWebhookDeliveryException was not thrown');
        } catch (RetryableWebhookDeliveryException $e) {
            self::assertSame(2500, $e->getDelay());
        }
    }

    public function testProcessWorksWithoutLogger(): void
    {
        $processor = new RetryableErrorWebhookResponseProcessor(
            new MockHttpClient(),
            $this->retryStrategy
        );

        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::never())
            ->method('getId');
        $webhook->expects(self::never())
            ->method('getNotificationUrl');

        $response = $this->createResponse('Error', 500);

        // Must not throw
        $result = $processor->process($response, $webhook, 'msg', false);
        self::assertFalse($result);
    }

    /**
     * Returns a response properly initialized via MockHttpClient so that getInfo() and getContent() work.
     */
    private function createResponse(string $body, int $statusCode, array $extraOptions = []): ResponseInterface
    {
        $client = new MockHttpClient([
            new MockResponse($body, array_merge(['http_code' => $statusCode], $extraOptions))
        ]);

        return $client->request('GET', 'https://example.com/webhook');
    }
}
