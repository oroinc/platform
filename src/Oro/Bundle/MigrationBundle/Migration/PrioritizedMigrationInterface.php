<?php

namespace Oro\Bundle\MigrationBundle\Migration;

/**
 * Prioritized Migration interface needs to be implemented by migrations,
 * which needs to have a specific priority.
 * This works for all migrations, except migrations, which added in listeners.
 * If Migration do not implement Prioritized Migration interface it has 0 priority.
 */
interface PrioritizedMigrationInterface
{
    /**
     * Get the priority of this migration
     *
     * @return integer
     */
    public function getPriority();
}
