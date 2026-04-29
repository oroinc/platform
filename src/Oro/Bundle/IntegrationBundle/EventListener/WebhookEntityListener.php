<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotifierInterface;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;

/**
 * Entity listener that sends webhook notifications on entity changes.
 */
final class WebhookEntityListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    private array $scheduledNotifications = [];
    private array $loadedTopics = [];

    public function __construct(
        private WebhookNotifierInterface $webhookNotifier,
        private WebhookConfigurationProvider $webhookConfigurationProvider
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $entity = $args->getObject();
        $topic = $this->webhookConfigurationProvider
            ->getTopicNameByEntityEvent($entity, WebhookConfigurationProvider::EVENT_CREATE);
        if (!$this->isNotificationAllowed($entity, $topic, $args)) {
            return;
        }

        // Postpone notification sending until flush to be sure that all relations are ready for serialization.
        $this->scheduledNotifications[] = [$topic, $entity];
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $entity = $args->getObject();
        $topic = $this->webhookConfigurationProvider
            ->getTopicNameByEntityEvent($entity, WebhookConfigurationProvider::EVENT_UPDATE);
        if (!$this->isNotificationAllowed($entity, $topic, $args)) {
            return;
        }

        $this->webhookNotifier->sendEntityEventNotification($topic, $entity);
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $entity = $args->getObject();
        $topic = $this->webhookConfigurationProvider
            ->getTopicNameByEntityEvent($entity, WebhookConfigurationProvider::EVENT_DELETE);
        if (!$this->isNotificationAllowed($entity, $topic, $args)) {
            return;
        }

        $this->webhookNotifier->sendEntityEventNotification($topic, $entity);
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->scheduledNotifications) {
            return;
        }

        foreach ($this->scheduledNotifications as $notification) {
            $this->webhookNotifier->sendEntityEventNotification(...$notification);
        }

        $this->clearStorages();
    }

    public function onClear()
    {
        $this->clearStorages();
    }

    private function clearStorages()
    {
        $this->scheduledNotifications = [];
        $this->loadedTopics = [];
    }

    private function isNotificationAllowed(
        object $entity,
        string $topic,
        LifecycleEventArgs $args
    ): bool {
        if (!$this->webhookConfigurationProvider->isEntityAccessibleByWebhooks($entity)) {
            return false;
        }

        if (!isset($this->loadedTopics[$topic])) {
            $repo = $args->getObjectManager()->getRepository(WebhookProducerSettings::class);
            $this->loadedTopics[$topic] = $repo->hasActiveWebhooks($topic);
        }

        return $this->loadedTopics[$topic];
    }
}
