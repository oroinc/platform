<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\ProcessSingleWebhookNotificationProcessor;
use Oro\Bundle\IntegrationBundle\Async\Topic\ProcessSingleWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Exception\RetryableWebhookDeliveryException;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotificationSenderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProcessSingleWebhookNotificationProcessorTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private WebhookNotificationSenderInterface&MockObject $notificationSender;
    private JobRunner&MockObject $jobRunner;
    private ProcessSingleWebhookNotificationProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->notificationSender = $this->createMock(WebhookNotificationSenderInterface::class);
        $this->jobRunner = $this->createMock(JobRunner::class);

        $this->processor = new ProcessSingleWebhookNotificationProcessor(
            $this->jobRunner,
            $this->entityManager,
            $this->notificationSender
        );
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [ProcessSingleWebhookNotificationTopic::getName()],
            ProcessSingleWebhookNotificationProcessor::getSubscribedTopics()
        );
    }

    public function testProcessSuccessfully(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(WebhookProducerSettings::class, 'webhook_123')
            ->willReturn($webhook);

        $this->notificationSender->expects(self::once())
            ->method('send')
            ->with(
                $webhook,
                ['id' => 1, 'name' => 'Test'],
                1234567890,
                'test-integrity-id-123',
                ['entity_class' => 'Order', 'entity_id' => 99],
                true
            )
            ->willReturn(true);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $closure) {
                return $closure();
            });

        $message = new Message();
        $message->setBody([
            'webhook_id' => 'webhook_123',
            'event_data' => ['id' => 1, 'name' => 'Test'],
            'timestamp' => 1234567890,
            'job_id' => 42,
            'message_id' => 'test-integrity-id-123',
            'metadata' => ['entity_class' => 'Order', 'entity_id' => 99],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessUnsuccessfullSend(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(WebhookProducerSettings::class, 'webhook_123')
            ->willReturn($webhook);

        $this->notificationSender->expects(self::once())
            ->method('send')
            ->with(
                $webhook,
                ['id' => 1, 'name' => 'Test'],
                1234567890,
                'test-integrity-id-123',
                ['entity_class' => 'Order', 'entity_id' => 99],
                true
            )
            ->willReturn(false);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $closure) {
                return $closure();
            });

        $message = new Message();
        $message->setBody([
            'webhook_id' => 'webhook_123',
            'event_data' => ['id' => 1, 'name' => 'Test'],
            'timestamp' => 1234567890,
            'job_id' => 42,
            'message_id' => 'test-integrity-id-123',
            'metadata' => ['entity_class' => 'Order', 'entity_id' => 99],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessRejectWhenWebhookNotFound(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Webhook producer settings not found',
                [
                    'webhook_id' => 'webhook_456',
                    'message_id' => 'test-integrity-id-456'
                ]
            );

        $this->processor->setLogger($logger);

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(WebhookProducerSettings::class, 'webhook_456')
            ->willReturn(null);

        $this->notificationSender->expects(self::never())
            ->method('send');

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $closure) {
                return $closure();
            });

        $message = new Message();
        $message->setBody([
            'webhook_id' => 'webhook_456',
            'event_data' => ['id' => 2],
            'timestamp' => 1234567899,
            'job_id' => 42,
            'message_id' => 'test-integrity-id-456',
            'metadata' => [],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessAckWhenWebhookDisabled(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'Webhook is disabled, skipping',
                [
                    'webhook_id' => 'webhook_789',
                    'message_id' => 'test-integrity-id-789'
                ]
            );

        $this->processor->setLogger($logger);

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(WebhookProducerSettings::class, 'webhook_789')
            ->willReturn($webhook);

        $this->notificationSender->expects(self::never())
            ->method('send');

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $closure) {
                return $closure();
            });

        $message = new Message();
        $message->setBody([
            'webhook_id' => 'webhook_789',
            'event_data' => ['id' => 3],
            'timestamp' => 1234567800,
            'job_id' => 42,
            'message_id' => 'test-integrity-id-789',
            'metadata' => [],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessRejectOnException(): void
    {
        $exception = new \RuntimeException('Test exception');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to process single webhook',
                self::callback(function ($context) use ($exception) {
                    return isset($context['message'])
                        && isset($context['exception'])
                        && $context['exception'] === $exception;
                })
            );

        $this->processor->setLogger($logger);

        $this->entityManager->expects(self::once())
            ->method('find')
            ->willThrowException($exception);

        $this->notificationSender->expects(self::never())
            ->method('send');

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $closure) {
                return $closure();
            });

        $message = new Message();
        $message->setBody([
            'webhook_id' => 'webhook_error',
            'event_data' => ['id' => 4],
            'timestamp' => 1234567700,
            'job_id' => 42,
            'message_id' => 'test-integrity-id-error',
            'metadata' => [],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessRedeliveryWhenRetryableException(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(WebhookProducerSettings::class, 'webhook_retry')
            ->willReturn($webhook);

        $retryableException = new RetryableWebhookDeliveryException('Too many requests', 429);
        $this->notificationSender->expects(self::once())
            ->method('send')
            ->willThrowException($retryableException);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Redelivering webhook notification',
                [
                    'webhook_id' => 'webhook_retry',
                    'message_id' => 'test-message-id-retry'
                ]
            );

        $this->processor->setLogger($logger);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $closure) {
                return $closure();
            });

        $message = new Message();
        $message->setBody([
            'webhook_id' => 'webhook_retry',
            'event_data' => ['id' => 5],
            'timestamp' => 1234567500,
            'job_id' => 42,
            'message_id' => 'test-message-id-retry',
            'metadata' => [],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::REQUEUE, $result);
    }

    public function testProcessRedeliveryWithDelayWhenRetryableExceptionHasDelay(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(WebhookProducerSettings::class, 'webhook_retry_delay')
            ->willReturn($webhook);

        $retryableException = new RetryableWebhookDeliveryException('Too many requests', 429);
        $retryableException->setDelay(5000);
        $this->notificationSender->expects(self::once())
            ->method('send')
            ->willThrowException($retryableException);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Redelivering webhook notification',
                [
                    'webhook_id' => 'webhook_retry_delay',
                    'message_id' => 'test-message-id-retry-delay'
                ]
            );

        $this->processor->setLogger($logger);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $closure) {
                return $closure();
            });

        $message = new Message();
        $message->setBody([
            'webhook_id' => 'webhook_retry_delay',
            'event_data' => ['id' => 6],
            'timestamp' => 1234567500,
            'job_id' => 42,
            'message_id' => 'test-message-id-retry-delay',
            'metadata' => [],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));
        self::assertEquals(MessageProcessorInterface::REQUEUE, $result);
        self::assertEquals(5, $message->getDelay());
    }

    public function testProcessRejectWhenNotificationSenderThrowsException(): void
    {
        $webhook = $this->createMock(WebhookProducerSettings::class);
        $webhook->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $exception = new \RuntimeException('Sender error');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to process single webhook',
                self::callback(function ($context) use ($exception) {
                    return isset($context['message'])
                        && isset($context['exception'])
                        && $context['exception'] === $exception;
                })
            );

        $this->processor->setLogger($logger);

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(WebhookProducerSettings::class, 'webhook_sender_error')
            ->willReturn($webhook);

        $this->notificationSender->expects(self::once())
            ->method('send')
            ->willThrowException($exception);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function ($jobId, $closure) {
                return $closure();
            });

        $message = new Message();
        $message->setBody([
            'webhook_id' => 'webhook_sender_error',
            'event_data' => ['id' => 5],
            'timestamp' => 1234567600,
            'job_id' => 42,
            'message_id' => 'test-integrity-id-sender-error',
            'metadata' => [],
        ]);

        $result = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }
}
