<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\SearchBundle\Query\Query;

class IndexerPrepareQueryEvent extends Event
{
    const EVENT_NAME = "oro_search.indexed_prepare_query";

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var string
     */
    protected $searchHandlerState;

    /**
     * @param Query $query
     * @param string $searchHandlerState
     */
    public function __construct(Query $query, $searchHandlerState)
    {
        $this->query = $query;
        $this->searchHandlerState = $searchHandlerState;
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

    /**
     * @return string
     */
    public function getSearchHandlerState()
    {
        return $this->searchHandlerState;
    }

    /**
     * @param string $searchHandlerState
     */
    public function setSearchHandlerState($searchHandlerState)
    {
        $this->searchHandlerState = $searchHandlerState;
    }
}
