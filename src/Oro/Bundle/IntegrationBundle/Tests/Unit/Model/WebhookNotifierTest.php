<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Async\Topic\SendWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Entity\Repository\WebhookProducerSettingsRepository;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotifier;
use Oro\Bundle\IntegrationBundle\Provider\WebhookEventDataProviderInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WebhookNotifierTest extends TestCase
{
    use EntityTrait;

    private DoctrineHelper&MockObject $doctrineHelper;
    private WebhookEventDataProviderInterface&MockObject $eventDataProvider;
    private MessageProducerInterface&MockObject $messageProducer;
    private WebhookProducerSettingsRepository&MockObject $repository;
    private LoggerInterface&MockObject $logger;
    private WebhookNotifier $notifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDataProvider = $this->createMock(WebhookEventDataProviderInterface::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->notifier = new WebhookNotifier(
            $this->doctrineHelper,
            $this->eventDataProvider,
            $this->messageProducer
        );
        $this->notifier->setLogger($this->logger);
    }

    public function testSendEntityEventNotificationSuccess(): void
    {
        $topic = 'order.created';
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $entityId = 123;
        $eventData = ['id' => 123, 'name' => 'Test'];

        $this->expectRepositoryCheck($topic, true);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->eventDataProvider->expects(self::once())
            ->method('getEventData')
            ->with($entityClass, $entityId)
            ->willReturn($eventData);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                SendWebhookNotificationTopic::getName(),
                self::callback(function ($message) use ($topic, $eventData, $entityClass, $entityId) {
                    return $message['topic'] === $topic
                        && $message['event_data'] === $eventData
                        && $message['entity_class'] === $entityClass
                        && $message['entity_id'] === $entityId
                        && isset($message['timestamp'])
                        && isset($message['message_id'])
                        && is_string($message['message_id'])
                        && !empty($message['message_id']);
                })
            );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(
                'Webhook notification queued for async processing',
                self::callback(function ($context) use ($topic) {
                    return $context['topic'] === $topic
                        && isset($context['message_id'])
                        && is_string($context['message_id'])
                        && !empty($context['message_id']);
                })
            );

        $this->notifier->sendEntityEventNotification($topic, $entity);
    }

    public function testSendEntityEventNotificationWithNoActiveWebhooks(): void
    {
        $topic = 'order.created';
        $entity = new \stdClass();

        $this->expectRepositoryCheck($topic, false);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityClass');

        $this->eventDataProvider->expects(self::never())
            ->method('getEventData');

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->notifier->sendEntityEventNotification($topic, $entity);
    }

    public function testSendEntityEventNotificationHandlesExceptionDuringDataRetrieval(): void
    {
        $topic = 'order.updated';
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $entityId = 456;
        $exception = new \RuntimeException('Data retrieval failed');

        $this->expectRepositoryCheck($topic, true);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->eventDataProvider->expects(self::once())
            ->method('getEventData')
            ->with($entityClass, $entityId)
            ->willThrowException($exception);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to serialize entity for webhook',
                [
                    'entity' => $entityClass,
                    'entity_id' => $entityId,
                    'error' => 'Data retrieval failed'
                ]
            );

        $this->notifier->sendEntityEventNotification($topic, $entity);
    }

    public function testSendNotificationSuccess(): void
    {
        $topic = 'product.deleted';
        $eventData = ['id' => 789, 'name' => 'Product Name'];

        $this->expectRepositoryCheck($topic, true);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                SendWebhookNotificationTopic::getName(),
                self::callback(function ($message) use ($topic, $eventData) {
                    return $message['topic'] === $topic
                        && $message['event_data'] === $eventData
                        && $message['entity_class'] === null
                        && $message['entity_id'] === null
                        && isset($message['timestamp'])
                        && isset($message['message_id'])
                        && is_string($message['message_id'])
                        && !empty($message['message_id']);
                })
            );

        $this->logger->expects(self::once())
            ->method('debug')
            ->with(
                'Webhook notification queued for async processing',
                self::callback(function ($context) use ($topic) {
                    return $context['topic'] === $topic
                        && isset($context['message_id'])
                        && is_string($context['message_id'])
                        && !empty($context['message_id']);
                })
            );

        $this->notifier->sendNotification($topic, $eventData);
    }

    public function testSendNotificationWithNoActiveWebhooks(): void
    {
        $topic = 'product.deleted';
        $eventData = ['id' => 789];

        $this->expectRepositoryCheck($topic, false);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->logger->expects(self::never())
            ->method('debug');

        $this->notifier->sendNotification($topic, $eventData);
    }

    public function testSendNotificationHandlesMessageProducerException(): void
    {
        $topic = 'order.created';
        $eventData = ['id' => 100];
        $exception = new \RuntimeException('Message queue error');

        $this->expectRepositoryCheck($topic, true);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to queue webhook notification',
                [
                    'topic' => $topic,
                    'exception' => $exception
                ]
            );

        $this->notifier->sendNotification($topic, $eventData);
    }

    public function testSendEntityEventNotificationHandlesMessageProducerException(): void
    {
        $topic = 'customer.updated';
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $entityId = 999;
        $eventData = ['id' => 999, 'email' => 'test@example.com'];
        $exception = new \RuntimeException('Queue is full');

        $this->expectRepositoryCheck($topic, true);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->eventDataProvider->expects(self::once())
            ->method('getEventData')
            ->with($entityClass, $entityId)
            ->willReturn($eventData);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to queue webhook notification',
                [
                    'topic' => $topic,
                    'exception' => $exception
                ]
            );

        $this->notifier->sendEntityEventNotification($topic, $entity);
    }

    public function testSendEntityEventNotificationWithoutLogger(): void
    {
        $notifier = new WebhookNotifier(
            $this->doctrineHelper,
            $this->eventDataProvider,
            $this->messageProducer
        );

        $topic = 'order.created';
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $entityId = 123;
        $eventData = ['id' => 123];

        $this->expectRepositoryCheck($topic, true);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);

        $this->eventDataProvider->expects(self::once())
            ->method('getEventData')
            ->with($entityClass, $entityId)
            ->willReturn($eventData);

        $this->messageProducer->expects(self::once())
            ->method('send');

        // Should not throw exception when logger is not set
        $notifier->sendEntityEventNotification($topic, $entity);
    }

    private function expectRepositoryCheck(string $topic, bool $hasActive): void
    {
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(WebhookProducerSettings::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('hasActiveWebhooks')
            ->with($topic)
            ->willReturn($hasActive);
    }
}
