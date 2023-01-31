<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The event listener for Doctrine events that intended to dispatch notification events
 * when entities are created, updated or removed.
 */
class DoctrineListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /** @var EntityPool */
    private $entityPool;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EntityPool $entityPool, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityPool = $entityPool;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->enabled) {
            $this->entityPool->persistAndFlush($args->getEntityManager());
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        if ($this->enabled) {
            $this->dispatch('oro.notification.event.entity_post_update', $args->getEntity());
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        if ($this->enabled) {
            $this->dispatch('oro.notification.event.entity_post_persist', $args->getEntity());
        }
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        if ($this->enabled) {
            $this->dispatch('oro.notification.event.entity_post_remove', $args->getEntity());
        }
    }

    /**
     * @param string $eventName
     * @param object $entity
     */
    private function dispatch($eventName, $entity)
    {
        $this->eventDispatcher->dispatch(new NotificationEvent($entity), $eventName);
    }
}
