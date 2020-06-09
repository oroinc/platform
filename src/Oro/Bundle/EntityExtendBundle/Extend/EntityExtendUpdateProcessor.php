<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * Provides a way to update the database and all related caches to reflect changes made in extended entities.
 */
class EntityExtendUpdateProcessor extends EntityProcessor
{
    /**
     * Updates the database and all related caches to reflect changes made in extended entities.
     *
     * @return bool
     */
    public function processUpdate(): bool
    {
        return $this->updateDatabase(true, true);
    }
}
