<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for layout updates that can be conditionally applied based on context.
 *
 * Implementations of this interface determine whether a layout update should be applied
 * by evaluating the current layout context.
 */
interface IsApplicableLayoutUpdateInterface
{
    /**
     * @param ContextInterface $context
     * @return bool
     */
    public function isApplicable(ContextInterface $context);
}
