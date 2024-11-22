<?php

namespace Oro\Component\Action\Event;

/**
 * BC Layer.
 * Interface that represents event factory to create compatibility focused ExtendableActionEvent instances.
 */
interface ExtendableActionEventFactoryInterface
{
    public function createEvent($context): ExtendableActionEvent;
}
