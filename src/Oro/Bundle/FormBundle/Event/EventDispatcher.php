<?php

namespace Oro\Bundle\FormBundle\Event;

use Oro\Bundle\FormBundle\Event\FormHandler\FormAwareInterface;
use Symfony\Component\EventDispatcher\ImmutableEventDispatcher;

/**
 * Extends ImmutableEventDispatcher with dispatching extra event for FormAwareInterface
 */
class EventDispatcher extends ImmutableEventDispatcher
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event, string $eventName = null): object
    {
        parent::dispatch($event, $eventName);

        if ($event instanceof FormAwareInterface) {
            parent::dispatch($event, $eventName . '.' . $event->getForm()->getName());
        }

        return $event;
    }
}
