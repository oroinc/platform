<?php

namespace Oro\Bundle\SearchBundle\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SearchBundle\Factory\QueryFactory;
use Oro\Bundle\SearchBundle\Factory\QueryFactoryInterface;

class SearchDatasource implements DatasourceInterface
{
    const TYPE = 'search';

    /** @var QueryFactoryInterface */
    protected $queryFactory;

    /** @var SearchQueryInterface */
    protected $query;

    /**
     * @param QueryFactoryInterface $factory
     */
    public function __construct(QueryFactoryInterface $factory)
    {
        $this->queryFactory = $factory;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $this->query = $this->queryFactory->create($grid, $config);

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
     * @return SearchQueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * The SearchQuery is a builder itself.
     *
     * @return SearchQueryInterface
     */
    public function getQueryBuilder()
    {
        return $this->getQuery();
    }
}
