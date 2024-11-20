<?php

namespace Oro\Bundle\ActionBundle\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Event dispatcher wrapper for action groups to dispatch a set of related events.
 */
class ActionGroupEventDispatcher
{
    private const EVENT_PREFIX = 'oro_action_group';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function dispatch(
        ActionGroupEvent $event
    ): void {
        $eventName = $event->getName();
        $this->eventDispatcher->dispatch($event, sprintf('%s.%s', static::EVENT_PREFIX, $eventName));

        $actionGroupName = $event->getActionGroupDefinition()->getName();
        $this->eventDispatcher->dispatch(
            $event,
            sprintf('%s.%s.%s', static::EVENT_PREFIX, $actionGroupName, $eventName)
        );
    }
}
