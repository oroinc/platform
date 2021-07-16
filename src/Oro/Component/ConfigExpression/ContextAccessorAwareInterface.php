<?php

namespace Oro\Component\ConfigExpression;

interface ContextAccessorAwareInterface
{
    /**
     * Sets the context accessor.
     */
    public function setContextAccessor(ContextAccessorInterface $contextAccessor);
}
