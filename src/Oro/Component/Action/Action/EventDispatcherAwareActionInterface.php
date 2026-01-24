<?php

namespace Oro\Component\Action\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the contract for actions that require event dispatcher injection.
 *
 * Actions implementing this interface can dispatch events during their execution,
 * enabling event-driven workflows and extensibility through event listeners.
 */
interface EventDispatcherAwareActionInterface
{
    /**
     * Add event dispatcher to the action
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher);
}
