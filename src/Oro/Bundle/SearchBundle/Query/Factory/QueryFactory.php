<?php

namespace Oro\Bundle\SearchBundle\Query\Factory;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Extension\IndexerQuery;

class QueryFactory implements QueryFactoryInterface
{
    /** @var Indexer */
    protected $indexer;

    /**
     * @param Indexer $indexer
     */
    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * {@inheritdoc}
     */
    public function create(DatagridInterface $grid, array $config)
    {
        return new IndexerQuery(
            $this->indexer,
            $this->indexer->select()
        );
    }
}
