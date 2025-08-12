<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * Represents a query that should be used to load identifiers of entities.
 */
interface LoadEntityIdsQueryInterface
{
    /**
     * Gets identifiers of entities.
     */
    public function getEntityIds(): array;

    /**
     * Gets the total number of entities.
     */
    public function getEntityTotalCount(): int;
}
