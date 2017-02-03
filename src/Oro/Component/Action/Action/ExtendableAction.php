<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Exception\ExtendableEventNameMissingException;

class ExtendableAction extends AbstractAction
{
    const NAME = 'extendable';

    /**
     * @var string[]
     */
    protected $subscribedEvents;

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $event = new ExtendableActionEvent($context);
        foreach ($this->subscribedEvents as $eventName) {
            if (!$this->eventDispatcher->hasListeners($eventName)) {
                continue;
            }

            $this->eventDispatcher->dispatch($eventName, $event);
        }
    }

    /**
     * Allowed options:
     *  - events (required) list of events the action dispatches
     */
    public function initialize(array $options)
    {
        if (!array_key_exists('events', $options)) {
            throw new ExtendableEventNameMissingException(
                sprintf(
                    'You need to specify a list of event names for the "@%s" action type with "events" config key',
                    self::NAME
                )
            );
        }
        $this->subscribedEvents = $options['events'];

        return $this;
    }
}
