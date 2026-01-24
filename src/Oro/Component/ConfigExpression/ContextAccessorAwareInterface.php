<?php

namespace Oro\Component\ConfigExpression;

/**
 * Defines the contract for objects that require a context accessor to be injected.
 *
 * Classes implementing this interface can receive a {@see ContextAccessor} instance, which they use
 * to access and manipulate values within an evaluation context. This is typically used by
 * expressions and conditions that need to read or write context data.
 */
interface ContextAccessorAwareInterface
{
    /**
     * Sets the context accessor.
     */
    public function setContextAccessor(ContextAccessorInterface $contextAccessor);
}
