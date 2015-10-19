<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\Context;

class GetListContext extends Context
{
    /** additional associations required to filter a list of entities */
    const JOINS = 'joins';

    /** a callback that can be used to calculate the total number of records in a list of entities */
    const TOTAL_COUNT_CALLBACK = 'totalCountCallback';

    /**
     * Gets additional associations required to filter a list of entities
     *
     * @return array|null
     */
    public function getJoins()
    {
        return $this->get(self::JOINS);
    }

    /**
     * Sets additional associations required to filter a list of entities
     *
     * @param array|null $joins
     */
    public function setJoins($joins)
    {
        $this->set(self::JOINS, $joins);
    }

    /**
     * Gets a callback that can be used to calculate the total number of records in a list of entities
     *
     * @return callable|null
     */
    public function getTotalCountCallback()
    {
        return $this->get(self::TOTAL_COUNT_CALLBACK);
    }

    /**
     * Sets a callback that can be used to calculate the total number of records in a list of entities
     *
     * @param callable|null $totalCount
     */
    public function setTotalCountCallback($totalCount)
    {
        $this->set(self::TOTAL_COUNT_CALLBACK, $totalCount);
    }
}
