<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;

class GetRelationshipContext extends SubresourceContext
{
    /** a callback that can be used to calculate the total number of related records */
    const TOTAL_COUNT_CALLBACK = 'totalCountCallback';

    /**
     * Gets a callback that can be used to calculate the total number of related records
     *
     * @return callable|null
     */
    public function getTotalCountCallback()
    {
        return $this->get(self::TOTAL_COUNT_CALLBACK);
    }

    /**
     * Sets a callback that can be used to calculate the total number of related records
     *
     * @param callable|null $totalCount
     */
    public function setTotalCountCallback($totalCount)
    {
        $this->set(self::TOTAL_COUNT_CALLBACK, $totalCount);
    }
}
