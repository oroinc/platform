<?php

namespace Oro\Bundle\IntegrationBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\Persistence\ObjectManager;
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
        if (!$this->webhookConfigurationProvider->isEntityAccessibleByWebhooks($entity)) {
            return;
        }

        $topic = $this->webhookConfigurationProvider
            ->getTopicNameByEntityEvent($entity, WebhookConfigurationProvider::EVENT_CREATE);

        // Postpone notification sending until flush to be sure that all relations are ready for serialization.
        $this->scheduledNotifications[] = [$topic, $entity];
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->notifyChanges(
            WebhookConfigurationProvider::EVENT_UPDATE,
            $args->getObject(),
            $args->getObjectManager()
        );
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $this->notifyChanges(
            WebhookConfigurationProvider::EVENT_DELETE,
            $args->getObject(),
            $args->getObjectManager()
        );
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
    }

    private function notifyChanges(string $event, object $entity, ObjectManager $objectManager): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->webhookConfigurationProvider->isEntityAccessibleByWebhooks($entity)) {
            return;
        }

        $topic = $this->webhookConfigurationProvider->getTopicNameByEntityEvent($entity, $event);

        $this->webhookNotifier->sendEntityEventNotification($topic, $entity);
        $id = $this->getEntityId($entity, $objectManager);
        if ($id !== null) {
            $this->webhookNotifier->sendEntityEventNotification($topic . '.' . $id, $entity);
        }
    }

    private function getEntityId(object $entity, ObjectManager $objectManager): int|string|null
    {
        $identifiers = $objectManager
            ->getClassMetadata(ClassUtils::getClass($entity))
            ->getIdentifierValues($entity);
        $id = reset($identifiers);

        return $id !== false ? $id : null;
    }
}
