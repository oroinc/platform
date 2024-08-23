<?php

namespace Oro\Bundle\WorkflowBundle\Event;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Event dispatcher wrapper for workflows to dispatch a set of events related to workflow.
 */
class EventDispatcher
{
    protected const EVENT_PREFIX = 'oro_workflow';

    private array $disabledEvents = [];

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function disableEvent(string $eventName): void
    {
        $this->disabledEvents[$eventName] = true;
    }

    public function restoreDisabledEvent(string $eventName): void
    {
        unset($this->disabledEvents[$eventName]);
    }

    public function dispatch(
        WorkflowItemAwareEvent $event,
        string $eventName,
        string $contextName = null
    ): void {
        if (!empty($this->disabledEvents[$eventName])) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        $workflowName = $workflowItem->getWorkflowName();

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
