<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Oro\Bundle\IntegrationBundle\Entity\Repository\WebhookProducerSettingsRepository;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\EventListener\WebhookEntityListener;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotifierInterface;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebhookEntityListenerTest extends TestCase
{
    private WebhookNotifierInterface&MockObject $webhookNotifier;
    private WebhookConfigurationProvider&MockObject $webhookConfigurationProvider;
    private EntityManagerInterface&MockObject $entityManager;
    private WebhookProducerSettingsRepository&MockObject $repository;
    private WebhookEntityListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->webhookNotifier = $this->createMock(WebhookNotifierInterface::class);
        $this->webhookConfigurationProvider = $this->createMock(WebhookConfigurationProvider::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(WebhookProducerSettingsRepository::class);

        $this->listener = new WebhookEntityListener(
            $this->webhookNotifier,
            $this->webhookConfigurationProvider
        );
    }

    public function testPostPersistSchedulesNotificationForFlush(): void
    {
        $entity = new \stdClass();
        $topic = 'test_topic';

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->with($entity, WebhookConfigurationProvider::EVENT_CREATE)
            ->willReturn($topic);

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->with($entity)
            ->willReturn(true);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(WebhookProducerSettings::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('hasActiveWebhooks')
            ->with($topic)
            ->willReturn(true);

        // Notification must NOT be sent immediately on persist
        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $args = new PostPersistEventArgs($entity, $this->entityManager);
        $this->listener->postPersist($args);
    }

    public function testPostPersistDoesNotScheduleWhenEntityNotAccessible(): void
    {
        $entity = new \stdClass();

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->with($entity, WebhookConfigurationProvider::EVENT_CREATE)
            ->willReturn('test_topic');

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->with($entity)
            ->willReturn(false);

        $this->entityManager->expects(self::never())
            ->method('getRepository');

        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $args = new PostPersistEventArgs($entity, $this->entityManager);
        $this->listener->postPersist($args);
    }

    public function testPostPersistDoesNotScheduleWhenNoActiveWebhooks(): void
    {
        $entity = new \stdClass();
        $topic = 'test_topic';

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->willReturn($topic);

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->willReturn(true);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('hasActiveWebhooks')
            ->willReturn(false);

        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $args = new PostPersistEventArgs($entity, $this->entityManager);
        $this->listener->postPersist($args);
    }

    public function testPostPersistDoesNothingWhenDisabled(): void
    {
        $this->listener->setEnabled(false);

        $this->webhookConfigurationProvider->expects(self::never())
            ->method('getTopicNameByEntityEvent');

        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $args = new PostPersistEventArgs(new \stdClass(), $this->entityManager);
        $this->listener->postPersist($args);
    }

    public function testPostFlushSendsScheduledNotificationsAndClearsThem(): void
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $topic = 'test_topic';

        // Schedule two notifications via postPersist
        $this->webhookConfigurationProvider->expects(self::exactly(2))
            ->method('getTopicNameByEntityEvent')
            ->willReturn($topic);

        $this->webhookConfigurationProvider->expects(self::exactly(2))
            ->method('isEntityAccessibleByWebhooks')
            ->willReturn(true);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('hasActiveWebhooks')
            ->willReturn(true);

        $this->listener->postPersist(new PostPersistEventArgs($entity1, $this->entityManager));
        $this->listener->postPersist(new PostPersistEventArgs($entity2, $this->entityManager));

        $this->webhookNotifier->expects(self::exactly(2))
            ->method('sendEntityEventNotification')
            ->withConsecutive(
                ['test_topic', $entity1],
                ['test_topic', $entity2]
            );

        $flushArgs = $this->createMock(PostFlushEventArgs::class);
        $this->listener->postFlush($flushArgs);
    }

    public function testPostFlushDoesNothingWhenNoScheduledNotifications(): void
    {
        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $flushArgs = $this->createMock(PostFlushEventArgs::class);
        $this->listener->postFlush($flushArgs);
    }

    public function testPostFlushClearsQueueAfterSending(): void
    {
        $entity = new \stdClass();
        $topic = 'test_topic';

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->willReturn($topic);
        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->willReturn(true);
        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->repository->expects(self::once())
            ->method('hasActiveWebhooks')
            ->willReturn(true);

        $this->listener->postPersist(new PostPersistEventArgs($entity, $this->entityManager));

        // First flush sends the notification
        $this->webhookNotifier->expects(self::once())
            ->method('sendEntityEventNotification');

        $flushArgs = $this->createMock(PostFlushEventArgs::class);
        $this->listener->postFlush($flushArgs);

        // Second flush must not resend
        $this->listener->postFlush($flushArgs);
    }

    public function testPostFlushDoesNothingWhenDisabled(): void
    {
        $this->listener->setEnabled(false);

        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testPostUpdateSendsNotification(): void
    {
        $entity = new \stdClass();
        $topic = 'test_topic';

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->with($entity, WebhookConfigurationProvider::EVENT_UPDATE)
            ->willReturn($topic);

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->with($entity)
            ->willReturn(true);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(WebhookProducerSettings::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('hasActiveWebhooks')
            ->with($topic)
            ->willReturn(true);

        $this->webhookNotifier->expects(self::once())
            ->method('sendEntityEventNotification')
            ->with($topic, $entity);

        $this->listener->postUpdate(new PostUpdateEventArgs($entity, $this->entityManager));
    }

    public function testPostUpdateDoesNotSendWhenEntityNotAccessible(): void
    {
        $entity = new \stdClass();

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->willReturn('test_topic');

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->willReturn(false);

        $this->entityManager->expects(self::never())
            ->method('getRepository');
        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $this->listener->postUpdate(new PostUpdateEventArgs($entity, $this->entityManager));
    }

    public function testPostUpdateDoesNotSendWhenNoActiveWebhooks(): void
    {
        $entity = new \stdClass();
        $topic = 'test_topic';

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->willReturn($topic);
        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->willReturn(true);
        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->repository->expects(self::once())
            ->method('hasActiveWebhooks')
            ->willReturn(false);
        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $this->listener->postUpdate(new PostUpdateEventArgs($entity, $this->entityManager));
    }

    public function testPostUpdateDoesNothingWhenDisabled(): void
    {
        $this->listener->setEnabled(false);

        $this->webhookConfigurationProvider->expects(self::never())
            ->method('getTopicNameByEntityEvent');
        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $this->listener->postUpdate(new PostUpdateEventArgs(new \stdClass(), $this->entityManager));
    }

    public function testPreRemoveSendsNotification(): void
    {
        $entity = new \stdClass();
        $topic = 'test_topic';

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->with($entity, WebhookConfigurationProvider::EVENT_DELETE)
            ->willReturn($topic);

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->with($entity)
            ->willReturn(true);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(WebhookProducerSettings::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('hasActiveWebhooks')
            ->with($topic)
            ->willReturn(true);

        $this->webhookNotifier->expects(self::once())
            ->method('sendEntityEventNotification')
            ->with($topic, $entity);

        $this->listener->preRemove(new PreRemoveEventArgs($entity, $this->entityManager));
    }

    public function testPreRemoveDoesNotSendWhenEntityNotAccessible(): void
    {
        $entity = new \stdClass();

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->willReturn('test_topic');

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->willReturn(false);

        $this->entityManager->expects(self::never())
            ->method('getRepository');
        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $this->listener->preRemove(new PreRemoveEventArgs($entity, $this->entityManager));
    }

    public function testPreRemoveDoesNotSendWhenNoActiveWebhooks(): void
    {
        $entity = new \stdClass();
        $topic = 'test_topic';

        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getTopicNameByEntityEvent')
            ->willReturn($topic);
        $this->webhookConfigurationProvider->expects(self::once())
            ->method('isEntityAccessibleByWebhooks')
            ->willReturn(true);
        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->repository->expects(self::once())
            ->method('hasActiveWebhooks')
            ->willReturn(false);
        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $this->listener->preRemove(new PreRemoveEventArgs($entity, $this->entityManager));
    }

    public function testPreRemoveDoesNothingWhenDisabled(): void
    {
        $this->listener->setEnabled(false);

        $this->webhookConfigurationProvider->expects(self::never())
            ->method('getTopicNameByEntityEvent');
        $this->webhookNotifier->expects(self::never())
            ->method('sendEntityEventNotification');

        $this->listener->preRemove(new PreRemoveEventArgs(new \stdClass(), $this->entityManager));
    }
}
