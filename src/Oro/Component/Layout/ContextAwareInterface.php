<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for objects that need to be aware of the layout context.
 *
 * Implementations of this interface can receive and store a reference to the layout context,
 * allowing them to access context variables during their operation.
 */
interface ContextAwareInterface
{
    /**
     * Sets the layout context.
     *
     * @param ContextInterface $context The layout context
     */
    public function setContext(ContextInterface $context);
}
