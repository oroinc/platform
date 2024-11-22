<?php

namespace Oro\Component\Action\Event;

/**
 * BC Layer.
 * Interface that represents event factory to create compatibility focused ExtendableConditionEvent instances.
 */
interface ExtendableConditionEventFactoryInterface
{
    public function createEvent($context): ExtendableConditionEvent;
}
