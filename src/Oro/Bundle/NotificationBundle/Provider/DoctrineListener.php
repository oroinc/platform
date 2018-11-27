<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The event listener for Doctrine events that intended to dispatch notification events
 * when entities are created, updated or removed.
 */
class DoctrineListener implements OptionalListenerInterface
{
    /** @var EntityPool */
    private $entityPool;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var bool */
    private $enabled = true;

    /**
     * @param EntityPool               $entityPool
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EntityPool $entityPool, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityPool = $entityPool;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if ($this->enabled) {
            $this->entityPool->persistAndFlush($args->getEntityManager());
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        if ($this->enabled) {
            $this->dispatch('oro.notification.event.entity_post_update', $args->getEntity());
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        if ($this->enabled) {
            $this->dispatch('oro.notification.event.entity_post_persist', $args->getEntity());
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
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
        $this->eventDispatcher->dispatch($eventName, new NotificationEvent($entity));
    }
}
