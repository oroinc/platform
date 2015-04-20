<?php

namespace Oro\Component\ConfigExpression;

interface ContextAccessorAwareInterface
{
    /**
     * Sets the context accessor.
     *
     * @param ContextAccessorInterface $contextAccessor
     */
    public function setContextAccessor(ContextAccessorInterface $contextAccessor);
}
