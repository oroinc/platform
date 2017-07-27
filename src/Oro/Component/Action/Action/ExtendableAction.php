<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Exception\InvalidParameterException;

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
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (!array_key_exists('events', $options)) {
            throw new InvalidParameterException('The required option "events" is missing.');
        }

        if (!is_array($options['events'])) {
            throw new InvalidParameterException(
                sprintf(
                    'The option "events" is expected to be of type "array", "%s" given.',
                    gettype($options['events'])
                )
            );
        }

        $this->subscribedEvents = $options['events'];

        return $this;
    }
}
