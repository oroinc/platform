<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotificationSender;
use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\WebhookRequestContext;
use Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor\WebhookRequestProcessorInterface;
use Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor\WebhookResponseProcessorInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class WebhookNotificationSenderTest extends TestCase
{
    use EntityTrait;

    private LoggerInterface&MockObject $logger;
    private WebhookRequestProcessorInterface&MockObject $requestProcessor;
    private WebhookResponseProcessorInterface&MockObject $responseProcessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestProcessor = $this->createMock(WebhookRequestProcessorInterface::class);
        $this->responseProcessor = $this->createMock(WebhookResponseProcessorInterface::class);
    }

    public function testSendCallsRequestProcessorWithCorrectContext(): void
    {
        $webhook = $this->createWebhook(1);
        $eventData = ['id' => 1];
        $timestamp = 1234567890;
        $messageId = 'msg-001';
        $metadata = ['entity_class' => 'Order', 'entity_id' => 42];

        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->with(
                self::callback(
                    static function (WebhookRequestContext $ctx) use ($eventData, $timestamp, $messageId, $metadata) {
                        $payload = $ctx->getPayload();

                        return $ctx->getHttpMethod() === 'POST'
                            && $payload['topic'] === 'order.created'
                            && $payload['eventData'] === $eventData
                            && $payload['timestamp'] === $timestamp
                            && $payload['messageId'] === $messageId
                            && $ctx->getMetadata() === $metadata
                            && isset($ctx->getHeaders()['Content-Type'])
                            && isset($ctx->getHeaders()['Webhook-Topic'])
                            && isset($ctx->getHeaders()['Webhook-Id']);
                    }
                ),
                $webhook,
                $messageId,
                false
            );

        $this->responseProcessor->expects(self::once())
            ->method('process')
            ->willReturn(true);

        $sender = $this->createSender(new MockHttpClient([new MockResponse('', ['http_code' => 200])]));
        $result = $sender->send($webhook, $eventData, $timestamp, $messageId, $metadata);

        self::assertTrue($result);
    }

    public function testSendBuildsCorrectHttpRequest(): void
    {
        $webhook = $this->createWebhook(2);
        $eventData = ['id' => 2];
        $timestamp = 1234567890;
        $messageId = 'msg-002';

        $mockResponse = new MockResponse('', ['http_code' => 200]);
        $this->responseProcessor->expects(self::once())
            ->method('process');

        $sender = $this->createSender(new MockHttpClient([$mockResponse]));
        $sender->send($webhook, $eventData, $timestamp, $messageId);

        self::assertEquals('POST', $mockResponse->getRequestMethod());
        self::assertEquals('https://example.com/webhook', $mockResponse->getRequestUrl());

        $options = $mockResponse->getRequestOptions();
        self::assertArrayHasKey('body', $options);
        self::assertArrayHasKey('headers', $options);
        self::assertEquals(0, $options['max_redirects']);

        $payload = json_decode($options['body'], true);
        self::assertEquals('order.created', $payload['topic']);
        self::assertEquals($eventData, $payload['eventData']);
        self::assertEquals($timestamp, $payload['timestamp']);
        self::assertEquals($messageId, $payload['messageId']);
    }

    public function testSendDelegatesResponseToResponseProcessor(): void
    {
        $webhook = $this->createWebhook(3);

        $this->responseProcessor->expects(self::once())
            ->method('process');

        $sender = $this->createSender(new MockHttpClient([new MockResponse('', ['http_code' => 200])]));
        $result = $sender->send($webhook, ['id' => 3], 1000, 'msg-003');

        self::assertFalse($result);
    }

    public function testSendAddsSignatureHeadersWhenSecretIsSet(): void
    {
        $webhook = $this->createWebhook(4, secret: 'my_secret');
        $eventData = ['id' => 4];
        $timestamp = 1234567890;
        $messageId = 'msg-004';

        $mockResponse = new MockResponse('', ['http_code' => 200]);
        $this->responseProcessor->expects(self::once())
            ->method('process');

        $sender = $this->createSender(new MockHttpClient([$mockResponse]));
        $sender->send($webhook, $eventData, $timestamp, $messageId);

        $options = $mockResponse->getRequestOptions();
        $headers = $options['headers'];

        $expectedPayload = json_encode([
            'topic' => 'order.created',
            'eventData' => $eventData,
            'timestamp' => $timestamp,
            'messageId' => $messageId,
        ], JSON_THROW_ON_ERROR);
        $expectedSignature = hash_hmac('sha256', $expectedPayload, 'my_secret');

        $signatureFound = false;
        $algorithmFound = false;
        foreach ($headers as $header) {
            if (str_starts_with($header, 'Webhook-Signature:')) {
                $signatureFound = true;
                self::assertEquals($expectedSignature, trim(substr($header, strlen('Webhook-Signature:'))));
            }
            if (str_starts_with($header, 'Webhook-Signature-Algorithm:')) {
                $algorithmFound = true;
                self::assertStringContainsString('HMAC-SHA256', $header);
            }
        }
        self::assertTrue($signatureFound, 'Webhook-Signature header must be present');
        self::assertTrue($algorithmFound, 'Webhook-Signature-Algorithm header must be present');
    }

    public function testSendNoSignatureHeadersWhenSecretIsNotSet(): void
    {
        $webhook = $this->createWebhook(5);
        $mockResponse = new MockResponse('', ['http_code' => 200]);
        $this->responseProcessor->expects(self::once())
            ->method('process');

        $sender = $this->createSender(new MockHttpClient([$mockResponse]));
        $sender->send($webhook, ['id' => 5], 1000, 'msg-005');

        $options = $mockResponse->getRequestOptions();
        foreach ($options['headers'] as $header) {
            self::assertStringNotContainsString('Webhook-Signature:', $header);
            self::assertStringNotContainsString('Webhook-Signature-Algorithm:', $header);
        }
    }

    public function testSendRespectsVerifySslFalse(): void
    {
        $webhook = $this->createWebhook(6, verifySsl: false);
        $mockResponse = new MockResponse('', ['http_code' => 200]);
        $this->responseProcessor->expects(self::once())
            ->method('process');

        $sender = $this->createSender(new MockHttpClient([$mockResponse]));
        $sender->send($webhook, ['id' => 6], 1000, 'msg-006');

        $options = $mockResponse->getRequestOptions();
        self::assertFalse($options['verify_peer']);
        self::assertFalse($options['verify_host']);
    }

    public function testSendRespectsVerifySslTrue(): void
    {
        $webhook = $this->createWebhook(7, verifySsl: true);
        $mockResponse = new MockResponse('', ['http_code' => 200]);
        $this->responseProcessor->expects(self::once())
            ->method('process');

        $sender = $this->createSender(new MockHttpClient([$mockResponse]));
        $sender->send($webhook, ['id' => 7], 1000, 'msg-007');

        $options = $mockResponse->getRequestOptions();
        self::assertTrue($options['verify_peer']);
        self::assertTrue($options['verify_host']);
    }

    public function testSendPassesThrowExceptionOnErrorToRequestProcessor(): void
    {
        $webhook = $this->createWebhook(8);

        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->with(self::anything(), $webhook, 'msg-008', true);

        $this->responseProcessor->expects(self::once())
            ->method('process');

        $sender = $this->createSender(new MockHttpClient([new MockResponse('', ['http_code' => 200])]));
        $sender->send($webhook, ['id' => 8], 1000, 'msg-008', [], true);
    }

    public function testSendPassesThrowExceptionOnErrorToResponseProcessor(): void
    {
        $webhook = $this->createWebhook(9);

        $this->responseProcessor->expects(self::once())
            ->method('process')
            ->with(self::anything(), $webhook, 'msg-009', true);

        $sender = $this->createSender(new MockHttpClient([new MockResponse('', ['http_code' => 200])]));
        $sender->send($webhook, ['id' => 9], 1000, 'msg-009', [], true);
    }

    public function testSendReturnsFalseAndLogsWhenExceptionOccurs(): void
    {
        $webhook = $this->createWebhook(10);
        $webhook->setTopic('order.created');
        $messageId = 'msg-010';
        $exception = new \RuntimeException('Connection refused');

        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send webhook notification',
                self::callback(static function (array $context) use ($messageId, $exception) {
                    return $context['message_id'] === $messageId
                        && $context['error'] === $exception->getMessage();
                })
            );

        $sender = $this->createSender(new MockHttpClient());
        $result = $sender->send($webhook, ['id' => 10], 1000, $messageId);

        self::assertFalse($result);
    }

    public function testSendRethrowsExceptionWhenThrowExceptionOnErrorTrue(): void
    {
        $webhook = $this->createWebhook(11);
        $exception = new \RuntimeException('Network error');

        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->willThrowException($exception);
        $this->logger->expects(self::once())
            ->method('error');

        $sender = $this->createSender(new MockHttpClient());

        $this->expectExceptionObject($exception);
        $sender->send($webhook, ['id' => 11], 1000, 'msg-011', [], true);
    }

    public function testSendLogsErrorWithWebhookContext(): void
    {
        $webhook = $this->createWebhook(12);
        $exception = new \RuntimeException('Timeout');

        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send webhook notification',
                [
                    'webhook_id' => 12,
                    'url' => 'https://example.com/webhook',
                    'topic' => 'order.created',
                    'error' => 'Timeout',
                    'message_id' => 'msg-012',
                ]
            );

        $sender = $this->createSender(new MockHttpClient());
        $sender->send($webhook, ['id' => 12], 1000, 'msg-012');
    }

    public function testSendRequestContextDefaultRequestOptions(): void
    {
        $webhook = $this->createWebhook(13);

        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->with(
                self::callback(static function (WebhookRequestContext $ctx) {
                    $opts = $ctx->getRequestOptions();

                    return $opts['max_redirects'] === 0
                        && isset($opts['verify_peer'])
                        && isset($opts['verify_host']);
                }),
                self::anything(),
                self::anything(),
                self::anything()
            );

        $this->responseProcessor->expects(self::once())
            ->method('process');
        $sender = $this->createSender(new MockHttpClient([new MockResponse('', ['http_code' => 200])]));
        $sender->send($webhook, ['id' => 13], 1000, 'msg-013');
    }

    public function testSendRequestProcessorCanMutateHttpMethod(): void
    {
        $webhook = $this->createWebhook(14);

        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(static function (WebhookRequestContext $ctx): void {
                $ctx->setHttpMethod('PUT');
            });

        $mockResponse = new MockResponse('', ['http_code' => 200]);
        $this->responseProcessor->expects(self::once())
            ->method('process');

        $sender = $this->createSender(new MockHttpClient([$mockResponse]));
        $sender->send($webhook, ['id' => 14], 1000, 'msg-014');

        self::assertEquals('PUT', $mockResponse->getRequestMethod());
    }

    public function testSendRequestProcessorCanMutatePayload(): void
    {
        $webhook = $this->createWebhook(15, secret: 'secret');
        $eventData = ['id' => 15, 'included' => ['rel1']];

        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(static function (WebhookRequestContext $ctx): void {
                $payload = $ctx->getPayload();
                unset($payload['eventData']['included']);
                $ctx->setPayload($payload);
            });

        $mockResponse = new MockResponse('', ['http_code' => 200]);
        $this->responseProcessor->expects(self::once())
            ->method('process');

        $sender = $this->createSender(new MockHttpClient([$mockResponse]));
        $sender->send($webhook, $eventData, 1000, 'msg-015');

        $options = $mockResponse->getRequestOptions();
        $payload = json_decode($options['body'], true);
        self::assertArrayNotHasKey('included', $payload['eventData']);

        // Signature must be computed from the mutated payload
        $expectedSig = hash_hmac('sha256', $options['body'], 'secret');
        $sigHeader = array_filter($options['headers'], static fn ($h) => str_starts_with($h, 'Webhook-Signature:'));
        $actualSig = trim(substr(reset($sigHeader), strlen('Webhook-Signature:')));
        self::assertEquals($expectedSig, $actualSig);
    }

    public function testSendWithEmptyMetadataByDefault(): void
    {
        $webhook = $this->createWebhook(16);

        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->with(
                self::callback(static fn (WebhookRequestContext $ctx) => $ctx->getMetadata() === []),
                self::anything(),
                self::anything(),
                self::anything()
            );

        $this->responseProcessor->expects(self::once())
            ->method('process');
        $sender = $this->createSender(new MockHttpClient([new MockResponse('', ['http_code' => 200])]));
        $sender->send($webhook, ['id' => 16], 1000, 'msg-016');
    }

    public function testSendJsonEncodingErrorIsHandledGracefully(): void
    {
        $webhook = $this->createWebhook(17);

        // Simulate JSON encoding failure by making requestProcessor mutate payload with INF value
        $this->requestProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(static function (WebhookRequestContext $ctx): void {
                $ctx->setPayload(['value' => INF]);
            });

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to send webhook notification', self::anything());

        $sender = $this->createSender(new MockHttpClient());
        $result = $sender->send($webhook, ['id' => 17], 1000, 'msg-017');

        self::assertFalse($result);
    }

    private function createWebhook(
        int $id,
        string $url = 'https://example.com/webhook',
        string $topic = 'order.created',
        ?string $secret = null,
        bool $verifySsl = true
    ): WebhookProducerSettings {
        /** @var WebhookProducerSettings $webhook */
        $webhook = $this->getEntity(WebhookProducerSettings::class, ['id' => $id]);
        $webhook->setNotificationUrl($url);
        $webhook->setTopic($topic);
        $webhook->setSecret($secret);
        $webhook->setVerifySsl($verifySsl);

        return $webhook;
    }

    private function createSender(MockHttpClient $httpClient): WebhookNotificationSender
    {
        $sender = new WebhookNotificationSender(
            $httpClient,
            $this->requestProcessor,
            $this->responseProcessor
        );
        $sender->setLogger($this->logger);

        return $sender;
    }
}
