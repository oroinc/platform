<?php

namespace Oro\Bundle\NotificationBundle\Event;

use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Decorates event dispatcher to register notification manager to {@see NotificationEvent}s
 * except those that are already registered in a built container.
 * @see \Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\SetRegisteredNotificationEventsCompilerPass
 */
class NotificationEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ServiceLink
     */
    private $notificationManagerLink;

    /**
     * Notification events registered in compiled DI container
     *
     * @var array
     */
    private $registeredNotificationEvents = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ServiceLink              $notificationManagerLink
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ServiceLink $notificationManagerLink
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->notificationManagerLink = $notificationManagerLink;
    }

    /**
     * @see \Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\SetRegisteredNotificationEventsCompilerPass
     * @param array $events
     */
    public function setRegisteredNotificationEvents(array $events)
    {
        $this->registeredNotificationEvents = $events;
    }

    /**
     * @inheritDoc
     */
    public function dispatch($eventName, Event $event = null)
    {
        if ($event instanceof NotificationEvent && !in_array($eventName, $this->registeredNotificationEvents, true)) {
            $this->eventDispatcher->addListener(
                $eventName,
                [
                    $this->notificationManagerLink->getService(),
                    'process',
                ],
                0
            );
            $this->registeredNotificationEvents[] = $eventName;
        }

        return $this->eventDispatcher->dispatch($eventName, $event);
    }

    /**
     * @inheritDoc
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
        return $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * @inheritDoc
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->eventDispatcher->addSubscriber($subscriber);
    }

    /**
     * @inheritDoc
     */
    public function removeListener($eventName, $listener)
    {
        return $this->eventDispatcher->removeListener($eventName, $listener);
    }

    /**
     * @inheritDoc
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->eventDispatcher->removeSubscriber($subscriber);
    }

    /**
     * @inheritDoc
     */
    public function getListeners($eventName = null)
    {
        return $this->eventDispatcher->getListeners($eventName);
    }

    /**
     * @inheritDoc
     */
    public function getListenerPriority($eventName, $listener)
    {
        return $this->eventDispatcher->getListenerPriority($eventName, $listener);
    }

    /**
     * @inheritDoc
     */
    public function hasListeners($eventName = null)
    {
        return $this->eventDispatcher->hasListeners($eventName);
    }

    /**
     * Proxies all method calls to the original service.
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->eventDispatcher->{$method}(...$arguments);
    }
}
