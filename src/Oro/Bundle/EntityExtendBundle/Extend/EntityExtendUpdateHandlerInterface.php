<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * Represents a service that is used to update the database schema and all related caches
 * to reflect changes made in extended entities.
 */
interface EntityExtendUpdateHandlerInterface
{
    /**
     * Updates the database schema and all related caches to reflect changes made in extended entities.
     */
    public function update(): EntityExtendUpdateResult;
}
