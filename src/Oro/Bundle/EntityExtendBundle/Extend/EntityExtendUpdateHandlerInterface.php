<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * Represents a service that is used to update the database and all related caches
 * to reflect changes made in extended entities.
 */
interface EntityExtendUpdateHandlerInterface
{
    /**
     * Updates the database and all related caches to reflect changes made in extended entities.
     *
     * @return EntityExtendUpdateResult
     */
    public function update(): EntityExtendUpdateResult;
}
