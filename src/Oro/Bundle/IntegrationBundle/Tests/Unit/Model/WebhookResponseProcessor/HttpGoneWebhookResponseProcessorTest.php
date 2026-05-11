<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\WebhookResponseProcessor;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor\HttpGoneWebhookResponseProcessor;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpGoneWebhookResponseProcessorTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry&MockObject $registry;
    private LoggerInterface&MockObject $logger;
    private HttpGoneWebhookResponseProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->processor = new HttpGoneWebhookResponseProcessor($this->registry);
        $this->processor->setLogger($this->logger);
    }

    public function testSupportsReturnsTrueForHttpGone(): void
    {
        $response = $this->createResponseMock(Response::HTTP_GONE);
        $webhook = $this->createMock(WebhookProducerSettings::class);

        self::assertTrue($this->processor->supports($response, $webhook));
    }

    /**
     * @dataProvider nonGoneStatusCodesProvider
     */
    public function testSupportsReturnsFalseForNonGoneStatusCodes(int $statusCode): void
    {
        $response = $this->createResponseMock($statusCode);
        $webhook = $this->createMock(WebhookProducerSettings::class);

        self::assertFalse($this->processor->supports($response, $webhook));
    }

    public function nonGoneStatusCodesProvider(): array
    {
        return [
            'HTTP 200' => [200],
            'HTTP 404' => [404],
            'HTTP 500' => [500],
            'HTTP 429' => [429],
        ];
    }

    public function testProcessRemovesWebhookAndLogsWarning(): void
    {
        $webhook = $this->getEntity(WebhookProducerSettings::class, ['id' => 'webhook-uuid-123']);
        $webhook->setNotificationUrl('http://127.0.0.1');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('remove')
            ->with($webhook)
            ->willReturnCallback(function (WebhookProducerSettings $webhook) {
                ReflectionUtil::setId($webhook, null);
            });
        $em->expects(self::once())
            ->method('flush')
            ->with($webhook);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(WebhookProducerSettings::class)
            ->willReturn($em);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Webhook was removed because the receiver returned a 410 GONE status code.',
                [
                    'webhook_id' => 'webhook-uuid-123',
                    'webhook_url' => 'http://127.0.0.1',
                    'message_id' => 'msg-001'
                ]
            );

        $response = $this->createResponseMock(Response::HTTP_GONE);
        $result = $this->processor->process($response, $webhook, 'msg-001');

        self::assertTrue($result);
    }

    public function testProcessReturnsTrueEvenWhenThrowExceptionOnErrorIsTrue(): void
    {
        $webhook = $this->getEntity(WebhookProducerSettings::class, ['id' => 'webhook-uuid-123']);
        $webhook->setNotificationUrl('http://127.0.0.1');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($em);
        $this->logger->expects(self::once())
            ->method('warning');

        $result = $this->processor->process(
            $this->createResponseMock(Response::HTTP_GONE),
            $webhook,
            'msg-002',
            true
        );

        self::assertTrue($result);
    }

    public function testProcessWorksWithoutLogger(): void
    {
        $processor = new HttpGoneWebhookResponseProcessor($this->registry);

        $webhook = $this->getEntity(WebhookProducerSettings::class, ['id' => 'webhook-uuid-123']);
        $webhook->setNotificationUrl('http://127.0.0.1');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($em);

        // Must not throw
        $result = $processor->process($this->createResponseMock(410), $webhook, 'msg');
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
