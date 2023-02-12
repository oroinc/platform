<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;

/**
 * The execution context for processors for "get_relationship" action.
 */
class GetRelationshipContext extends SubresourceContext
{
    /** a callback that can be used to calculate the total number of related records */
    private const TOTAL_COUNT_CALLBACK = 'totalCountCallback';

    /**
     * Gets a callback that can be used to calculate the total number of related records
     */
    public function getTotalCountCallback(): ?callable
    {
        return $this->get(self::TOTAL_COUNT_CALLBACK);
    }

    /**
     * Sets a callback that can be used to calculate the total number of related records
     */
    public function setTotalCountCallback(?callable $totalCount): void
    {
        $this->set(self::TOTAL_COUNT_CALLBACK, $totalCount);
    }
}
