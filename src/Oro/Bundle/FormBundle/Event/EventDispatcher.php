<?php

namespace Oro\Bundle\FormBundle\Event;

use Oro\Bundle\FormBundle\Event\FormHandler\FormAwareInterface;
use Symfony\Component\EventDispatcher\ImmutableEventDispatcher;
use Symfony\Contracts\EventDispatcher\Event;

class EventDispatcher extends ImmutableEventDispatcher
{
    /**
     * @param Event $event
     * @param string|null $eventName
     * @return Event|null
     */
    public function dispatch($event/*, string $eventName = null*/)
    {
        $eventName = 1 < \func_num_args() ? func_get_arg(1) : null;

        parent::dispatch($event, $eventName);

        if ($event instanceof FormAwareInterface) {
            parent::dispatch($event, $eventName . '.' . $event->getForm()->getName());
        }

        return $event;
    }
}
