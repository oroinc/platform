<?php

namespace Oro\Bundle\FormBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\ImmutableEventDispatcher;

use Oro\Bundle\FormBundle\Event\FormHandler\FormAwareInterface;

class EventDispatcher extends ImmutableEventDispatcher
{
    /**
     * @param string $eventName
     * @param Event|null $event
     * @return Event|null
     */
    public function dispatch($eventName, Event $event = null)
    {
        parent::dispatch($eventName, $event);

        if ($event instanceof FormAwareInterface) {
            parent::dispatch($eventName . '.' . $event->getForm()->getName(), $event);
        }

        return $event;
    }
}
