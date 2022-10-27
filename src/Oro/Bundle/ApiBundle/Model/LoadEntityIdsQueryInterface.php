<?php

namespace Oro\Bundle\ApiBundle\Model;

use Oro\Bundle\ApiBundle\Exception\InvalidSorterException;

/**
 * Represents a query that should be used to load identifiers of entities.
 */
interface LoadEntityIdsQueryInterface
{
    /**
     * Gets identifiers of entities.
     *
     * @throws InvalidSorterException if entity IDs cannot be loaded due to a requested sorting is not supported
     */
    public function getEntityIds(): array;

    /**
     * Gets the total number of entities.
     */
    public function getEntityTotalCount(): int;
}
