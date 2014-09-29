<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\SearchBundle\Query\Query;

class BeforeSearchEvent extends Event
{
    const EVENT_NAME = "oro_search.before_search";

    /**
     * @var Query
     */
    protected $query;

    /**
     * @param Query $query
     */
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
