<?php

namespace Oro\Bundle\FormBundle\Entity;

/**
 * Defines the contract for items that have a priority value.
 *
 * Implementations of this interface represent entities or data structures that support
 * priority-based ordering, allowing items to be sorted and displayed based on their
 * assigned priority values.
 */
interface PriorityItem
{
    /**
     * Get item priority
     *
     * @return int
     */
    public function getPriority();

    /**
     * Set item priority
     *
     * @param int $priority
     */
    public function setPriority($priority);
}
