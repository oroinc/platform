<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\WebhookResponseProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Exception\WebhookDeliveryException;
use Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor\FallbackWebhookResponseProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FallbackWebhookResponseProcessorTest extends TestCase
{
    private FallbackWebhookResponseProcessor $processor;
    private LoggerInterface&MockObject $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = new FallbackWebhookResponseProcessor();
        $this->processor->setLogger($this->logger);
    }

    /**
     * @dataProvider anyStatusCodeProvider
     */
    public function testSupportsAlwaysReturnsTrue(int $statusCode): void
    {
        $response = $this->createResponseMock($statusCode);
        $webhook = $this->createMock(WebhookProducerSettings::class);

        self::assertTrue($this->processor->supports($response, $webhook));
    }

    public function anyStatusCodeProvider(): array
    {
        return [
            'HTTP 200 OK' => [200],
            'HTTP 201 Created' => [201],
            'HTTP 400 Bad Request' => [400],
            'HTTP 404 Not Found' => [404],
            'HTTP 410 Gone' => [410],
            'HTTP 429 Too Many Requests' => [429],
            'HTTP 500 Server Error' => [500],
        ];
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

        $response = $this->createResponseMock(400);
        $response->expects(self::once())
            ->method('getContent')
            ->with(false)
            ->willReturn('Bad Request body');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Webhook notification endpoint returned non-success status code',
                [
                    'webhook_id' => 'webhook-id',
                    'url' => 'https://example.com',
                    'status_code' => 400,
                    'response_body' => 'Bad Request body',
                    'message_id' => 'msg-001',
                ]
            );

        $result = $this->processor->process($response, $webhook, 'msg-001', false);

        self::assertFalse($result);
    }

    public function testProcessThrowsWebhookDeliveryExceptionWhenFlagIsSet(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('getId')
            ->willReturn('id');
        $webhook->expects(self::once())
            ->method('getNotificationUrl')
            ->willReturn('https://example.com');

        $response = $this->createResponseMock(503);
        $response->expects(self::once())
            ->method('getContent')
            ->willReturn('Service Unavailable');
        $this->logger->expects(self::once())
            ->method('error');

        $this->expectException(WebhookDeliveryException::class);
        $this->expectExceptionMessage('Webhook notification endpoint returned non-success status code');
        $this->expectExceptionCode(503);

        $this->processor->process($response, $webhook, 'msg-002', true);
    }

    public function testProcessWorksWithoutLogger(): void
    {
        $processor = new FallbackWebhookResponseProcessor();

        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::never())
            ->method('getId');
        $webhook->expects(self::never())
            ->method('getNotificationUrl');

        $response = $this->createResponseMock(404);
        $response->expects(self::never())
            ->method('getContent');

        // Must not throw
        $result = $processor->process($response, $webhook, 'msg');
        self::assertFalse($result);
    }

    private function createResponseMock(int $statusCode): ResponseInterface&MockObject
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        return $response;
    }
}
