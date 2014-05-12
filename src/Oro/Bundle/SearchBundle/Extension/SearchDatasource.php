<?php

namespace Oro\Bundle\SearchBundle\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Extension\Pager\IndexerQuery;

class SearchDatasource implements DatasourceInterface
{
    const TYPE = 'search';

    /** @var Indexer */
    protected $indexer;

    /** @var IndexerQuery */
    protected $query;

    /**
     * @param Indexer $indexer
     */
    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $this->query = new IndexerQuery(
            $this->indexer,
            $this->indexer->select()
        );
        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $results = $this->query->execute();
        $rows    = [];
        foreach ($results as $result) {
            $rows[] = new ResultRecord($result);
        }

        return $rows;
    }

    /**
     * @return IndexerQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
