<?php

namespace Oro\Bundle\SearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;

/**
 * Creates search query instances for use in datagrids and other contexts.
 *
 * This factory class creates {@see IndexerQuery} objects that wrap the search indexer
 * and provide a query interface for executing searches. It is used by the datagrid
 * framework to instantiate search queries based on configuration.
 */
class QueryFactory implements QueryFactoryInterface
{
    /** @var Indexer */
    protected $indexer;

    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

    #[\Override]
    public function create(array $config = [])
    {
        return new IndexerQuery(
            $this->indexer,
            $this->indexer->select()
        );
    }
}
