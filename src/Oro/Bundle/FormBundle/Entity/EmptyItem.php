<?php

namespace Oro\Bundle\FormBundle\Entity;

/**
 * Defines the contract for items that can be checked for emptiness.
 *
 * Implementations of this interface represent entities or data structures that may
 * have an empty state, allowing callers to determine whether an item contains
 * meaningful data or is considered empty.
 */
interface EmptyItem
{
    /**
     * Is empty
     */
    public function isEmpty();
}
