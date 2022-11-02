<?php

namespace Oro\Bundle\SearchBundle\Event;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Identifies events which are aware of search query.
 */
interface SearchQueryAwareEventInterface
{
    /**
     * @return Query
     */
    public function getQuery();
}
