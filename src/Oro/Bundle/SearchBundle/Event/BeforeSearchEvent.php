<?php

namespace Oro\Bundle\SearchBundle\Event;

use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event which is triggered before search query is executed and gives possibility to adjust search query.
 */
class BeforeSearchEvent extends Event implements SearchQueryAwareEventInterface
{
    const EVENT_NAME = "oro_search.before_search";

    /**
     * @var Query
     */
    protected $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param Query $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }
}
