<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\WebhookResponseProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor\SuccessWebhookResponseProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SuccessWebhookResponseProcessorTest extends TestCase
{
    private SuccessWebhookResponseProcessor $processor;
    private LoggerInterface&MockObject $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = new SuccessWebhookResponseProcessor();
        $this->processor->setLogger($this->logger);
    }

    /**
     * @dataProvider successStatusCodesProvider
     */
    public function testSupportsReturnsTrueForSuccessStatusCodes(int $statusCode): void
    {
        $response = $this->createResponseMock($statusCode);
        $webhook = $this->createMock(WebhookProducerSettings::class);

        self::assertTrue($this->processor->supports($response, $webhook));
    }

    public function successStatusCodesProvider(): array
    {
        return [
            'HTTP 200 OK' => [200],
            'HTTP 201 Created' => [201],
            'HTTP 202 Accepted' => [202],
            'HTTP 204 No Content' => [204],
            'HTTP 299' => [299],
        ];
    }

    /**
     * @dataProvider nonSuccessStatusCodesProvider
     */
    public function testSupportsReturnsFalseForNonSuccessStatusCodes(int $statusCode): void
    {
        $response = $this->createResponseMock($statusCode);
        $webhook = $this->createMock(WebhookProducerSettings::class);

        self::assertFalse($this->processor->supports($response, $webhook));
    }

    public function nonSuccessStatusCodesProvider(): array
    {
        return [
            'HTTP 300 Multiple Choices' => [300],
            'HTTP 301 Redirect' => [301],
            'HTTP 400 Bad Request' => [400],
            'HTTP 404 Not Found' => [404],
            'HTTP 500 Server Error' => [500],
            'HTTP 410 Gone' => [410],
        ];
    }

    public function testProcessLogsInfoAndReturnsTrue(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('getId')
            ->willReturn('webhook-uuid');
        $webhook->expects(self::once())
            ->method('getNotificationUrl')
            ->willReturn('https://example.com/hook');
        $webhook->expects(self::once())
            ->method('getTopic')
            ->willReturn('order.created');

        $response = $this->createResponseMock(Response::HTTP_OK);

        $this->logger->expects(self::once())
            ->method('info')
            ->with(
                'Webhook notification sent successfully',
                [
                    'webhook_id' => 'webhook-uuid',
                    'url' => 'https://example.com/hook',
                    'topic' => 'order.created',
                    'status_code' => 200,
                    'message_id' => 'msg-001',
                ]
            );

        $result = $this->processor->process($response, $webhook, 'msg-001');

        self::assertTrue($result);
    }

    public function testProcessReturnsTrueRegardlessOfThrowExceptionOnError(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::any())
            ->method('getId')
            ->willReturn('id');
        $webhook->expects(self::any())
            ->method('getNotificationUrl')
            ->willReturn('https://example.com');
        $webhook->expects(self::any())
            ->method('getTopic')
            ->willReturn('topic');

        $response = $this->createResponseMock(201);
        $this->logger->expects(self::any())
            ->method('info');

        self::assertTrue($this->processor->process($response, $webhook, 'msg', false));
        self::assertTrue($this->processor->process($response, $webhook, 'msg', true));
    }

    public function testProcessWorksWithoutLogger(): void
    {
        $processor = new SuccessWebhookResponseProcessor();
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::any())
            ->method('getId')
            ->willReturn('id');
        $webhook->expects(self::any())
            ->method('getNotificationUrl')
            ->willReturn('https://example.com');
        $webhook->expects(self::any())
            ->method('getTopic')
            ->willReturn('topic');

        $response = $this->createResponseMock(200);

        // Must not throw NullPointerException
        $result = $processor->process($response, $webhook, 'msg');
        self::assertTrue($result);
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
