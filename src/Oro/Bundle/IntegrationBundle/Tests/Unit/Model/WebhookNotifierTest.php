<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Async\Topic\SendWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Entity\Repository\WebhookProducerSettingsRepository;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotifier;
use Oro\Bundle\IntegrationBundle\Provider\WebhookEventDataProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\UserBundle\Entity\User;
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
    private EntityOwnerAccessor&MockObject $ownerAccessor;
    private WebhookProducerSettingsRepository&MockObject $repository;
    private LoggerInterface&MockObject $logger;
    private WebhookNotifier $notifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDataProvider = $this->createMock(WebhookEventDataProviderInterface::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->ownerAccessor = $this->createMock(EntityOwnerAccessor::class);
        $this->repository = $this->createMock(WebhookProducerSettingsRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->notifier = new WebhookNotifier(
            $this->doctrineHelper,
            $this->eventDataProvider,
            $this->messageProducer,
            $this->ownerAccessor
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

        $this->expectEntityOwnership(
            $entity,
            $this->getEntity(User::class, ['id' => 7]),
            $this->getEntity(Organization::class, ['id' => 3])
        );

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                SendWebhookNotificationTopic::getName(),
                self::callback(function ($message) use ($topic, $eventData, $entityClass, $entityId) {
                    return $message['topic'] === $topic
                        && $message['event_data'] === $eventData
                        && $message['entity_class'] === $entityClass
                        && $message['entity_id'] === $entityId
                        && $message['entity_owner_id'] === 7
                        && $message['entity_organization_id'] === 3
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

    public function testSendEntityEventNotificationWhenEntityHasNoOwner(): void
    {
        $topic = 'order.created';
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $entityId = 123;
        $eventData = ['id' => 123];

        $this->expectRepositoryCheck($topic, true);
        $this->expectEntityData($entity, $entityClass, $entityId, $eventData);
        $this->expectEntityOwnership($entity, null, null);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                SendWebhookNotificationTopic::getName(),
                self::callback(function ($message) {
                    return $message['entity_owner_id'] === null
                        && $message['entity_organization_id'] === null;
                })
            );

        $this->logger->expects(self::never())
            ->method('error');

        $this->notifier->sendEntityEventNotification($topic, $entity);
    }

    public function testSendEntityEventNotificationWhenOwnershipCannotBeResolved(): void
    {
        $topic = 'order.created';
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $entityId = 123;
        $eventData = ['id' => 123];

        $this->expectRepositoryCheck($topic, true);
        $this->expectEntityData($entity, $entityClass, $entityId, $eventData);

        $exception = new InvalidEntityException('$object must be an object.');
        $this->ownerAccessor->expects(self::once())
            ->method('getOwner')
            ->with($entity)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Failed to get the entity ownership for webhook',
                [
                    'entity_class' => $entityClass,
                    'entity_id' => $entityId,
                    'exception' => $exception
                ]
            );

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                SendWebhookNotificationTopic::getName(),
                self::callback(function ($message) use ($eventData) {
                    return $message['event_data'] === $eventData
                        && $message['entity_owner_id'] === null
                        && $message['entity_organization_id'] === null;
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

        $this->ownerAccessor->expects(self::never())
            ->method('getOwner');

        $this->ownerAccessor->expects(self::never())
            ->method('getOrganization');

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

        $this->messageProducer->expects(self::exactly(2))
            ->method('send')
            ->with(
                SendWebhookNotificationTopic::getName(),
                self::callback(function ($message) use ($topic, $eventData) {
                    return $message['topic'] === $topic
                        && $message['event_data'] === $eventData
                        && $message['entity_class'] === null
                        && $message['entity_id'] === null
                        && array_key_exists('entity_owner_id', $message)
                        && $message['entity_owner_id'] === null
                        && array_key_exists('entity_organization_id', $message)
                        && $message['entity_organization_id'] === null
                        && isset($message['timestamp'])
                        && isset($message['message_id'])
                        && is_string($message['message_id'])
                        && !empty($message['message_id']);
                })
            );

        $this->logger->expects(self::exactly(2))
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
        // Second call added to check that repository check for hasActiveWebhooks is called only once
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
            $this->messageProducer,
            $this->ownerAccessor
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

    private function expectEntityData(
        object $entity,
        string $entityClass,
        int $entityId,
        array $eventData
    ): void {
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
    }

    private function expectEntityOwnership(object $entity, ?User $owner, ?Organization $organization): void
    {
        $this->ownerAccessor->expects(self::once())
            ->method('getOwner')
            ->with($entity)
            ->willReturn($owner);

        $this->ownerAccessor->expects(self::once())
            ->method('getOrganization')
            ->with($entity)
            ->willReturn($organization);
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
