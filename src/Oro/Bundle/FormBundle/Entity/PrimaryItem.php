<?php

namespace Oro\Bundle\FormBundle\Entity;

/**
 * Defines the contract for items that can be marked as primary.
 *
 * Implementations of this interface represent entities or data structures that support
 * a primary/non-primary designation, allowing one item in a collection to be marked
 * as the primary or default item.
 */
interface PrimaryItem
{
    /**
     * Is item primary
     *
     * @return bool
     */
    public function isPrimary();

    /**
     * Set item primary
     *
     * @param bool $value
     */
    public function setPrimary($value);
}
