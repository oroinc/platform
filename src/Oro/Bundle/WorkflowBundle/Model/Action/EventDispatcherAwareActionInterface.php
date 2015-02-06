<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface EventDispatcherAwareActionInterface
{
    /**
     * Add event dispatcher to the action
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher);
}
