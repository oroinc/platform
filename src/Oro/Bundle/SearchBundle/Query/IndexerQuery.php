<?php

namespace Oro\Bundle\SearchBundle\Query;

use Oro\Bundle\SearchBundle\Engine\Indexer;

/**
 * Wraps a search query for execution through the search indexer.
 *
 * This class extends {@see AbstractSearchQuery} to provide a wrapper around a {@see Query} object
 * that delegates method calls to the underlying query while using the search indexer
 * for actual query execution. It enables transparent integration of search queries
 * with the indexer infrastructure.
 */
class IndexerQuery extends AbstractSearchQuery
{
    /**
     * @var Indexer
     */
    protected $indexer;

    public function __construct(Indexer $indexer, Query $query)
    {
        $this->indexer = $indexer;
        $this->query   = $query;
    }

    public function __call($name, $args)
    {
        return call_user_func_array(array($this->query, $name), $args);
    }

    #[\Override]
    protected function query()
    {
        return $this->indexer->query($this->query);
    }
}
