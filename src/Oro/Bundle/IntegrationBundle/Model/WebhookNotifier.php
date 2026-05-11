<?php

namespace Oro\Bundle\IntegrationBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Async\Topic\SendWebhookNotificationTopic;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Provider\WebhookEventDataProviderInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Queues webhook notifications for async processing.
 *
 * Use this service to schedule non-blocking webhooks notification associated with a topic.
 */
class WebhookNotifier implements WebhookNotifierInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private DoctrineHelper $doctrineHelper,
        private WebhookEventDataProviderInterface $eventDataProvider,
        private MessageProducerInterface $messageProducer
    ) {
    }

    public function sendEntityEventNotification(string $topic, object $entity): void
    {
        if (!$this->hasActiveNotifications($topic)) {
            return;
        }

        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        try {
            $eventData = $this->eventDataProvider->getEventData($entityClass, $entityId);
            $this->peformNotificationSend($topic, $eventData, $entityClass, $entityId);
        } catch (\Throwable $e) {
            $this->logger?->error(
                'Failed to serialize entity for webhook',
                [
                    'entity' => $entityClass,
                    'entity_id' => $entityId,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    public function sendNotification(string $topic, array $eventData): void
    {
        if (!$this->hasActiveNotifications($topic)) {
            return;
        }

        $this->peformNotificationSend($topic, $eventData);
    }

    private function hasActiveNotifications(string $topic): bool
    {
        return $this->doctrineHelper
            ->getEntityRepository(WebhookProducerSettings::class)
            ->hasActiveWebhooks($topic);
    }

    private function peformNotificationSend(
        string $topic,
        array $eventData,
        ?string $entityClass = null,
        string|int|null $entityId = null
    ) {
        $messageId = UUIDGenerator::v4();
        try {
            $this->messageProducer->send(
                SendWebhookNotificationTopic::getName(),
                [
                    'topic' => $topic,
                    'message_id' => $messageId,
                    'timestamp' => time(),
                    'event_data' => $eventData,
                    'entity_class' => $entityClass,
                    'entity_id' => $entityId
                ]
            );

            $this->logger?->debug(
                'Webhook notification queued for async processing',
                [
                    'topic' => $topic,
                    'message_id' => $messageId
                ]
            );
        } catch (\Throwable $e) {
            $this->logger?->error(
                'Failed to queue webhook notification',
                [
                    'topic' => $topic,
                    'exception' => $e
                ]
            );
        }
    }
}
