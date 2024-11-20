<?php

namespace Oro\Bundle\ActionBundle\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Event dispatcher wrapper for operations to dispatch a set of related events.
 */
class OperationEventDispatcher
{
    private const EVENT_PREFIX = 'oro_operation';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function dispatch(
        OperationEvent $event
    ): void {
        $eventName = $event->getName();
        $this->eventDispatcher->dispatch($event, sprintf('%s.%s', static::EVENT_PREFIX, $eventName));

        $operationName = $event->getOperationDefinition()->getName();
        $this->eventDispatcher->dispatch(
            $event,
            sprintf('%s.%s.%s', static::EVENT_PREFIX, $operationName, $eventName)
        );
    }
}
