<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Event dispatcher wrapper for workflows to dispatch a set of events related to workflow.
 */
class EventDispatcher
{
    protected const EVENT_PREFIX = 'oro_workflow';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function dispatch(
        WorkflowItemAwareEvent $event,
        string $contextName = null
    ): void {
        $workflowItem = $event->getWorkflowItem();
        $workflowName = $workflowItem->getWorkflowName();
        $eventName = $event->getName();

        $this->eventDispatcher->dispatch($event, sprintf('%s.%s', static::EVENT_PREFIX, $eventName));
        $this->eventDispatcher->dispatch($event, sprintf('%s.%s.%s', static::EVENT_PREFIX, $workflowName, $eventName));

        if ($contextName) {
            $this->eventDispatcher->dispatch(
                $event,
                sprintf('%s.%s.%s.%s', static::EVENT_PREFIX, $workflowName, $eventName, $contextName)
            );
        }
    }
}
