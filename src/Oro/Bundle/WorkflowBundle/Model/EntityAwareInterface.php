<?php

namespace Oro\Bundle\WorkflowBundle\Model;

/**
 * Defines the contract for objects that are aware of and can provide a related entity.
 *
 * Implementations can retrieve the entity they are associated with, enabling entity-specific
 * operations and context management.
 */
interface EntityAwareInterface
{
    /**
     * Get related entity.
     *
     * @return object
     */
    public function getEntity();
}
