<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Datasource;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SearchBundle\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class SearchDatasource implements DatasourceInterface
{
    const TYPE = 'search';

    /** @var QueryFactoryInterface */
    protected $queryFactory;

    /** @var SearchQueryInterface */
    protected $searchQuery;

    /** @var DatagridInterface */
    protected $datagrid;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var YamlToSearchQueryConverter */
    protected $yamlToSearchQueryConverter;

    /**
     * @param QueryFactoryInterface    $factory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        QueryFactoryInterface $factory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->queryFactory               = $factory;
        $this->dispatcher                 = $eventDispatcher;
        $this->yamlToSearchQueryConverter = new YamlToSearchQueryConverter();
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $this->datagrid = $grid;

        $this->searchQuery = $this->queryFactory->create($grid, $config);

        $this->yamlToSearchQueryConverter->process($this->searchQuery, $config);

        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $results = $this->searchQuery->execute();
        /** @var Item[] $results */

        $event = new SearchResultBefore($this->datagrid, $this->searchQuery);
        $this->dispatcher->dispatch(SearchResultBefore::NAME, $event);

        $rows = [];
        foreach ($results as $result) {
            $resultRecord = new ResultRecord($result);
            $resultRecord->addData(
                array_merge(['id' => $result->getId()], $result->getSelectedData())
            );
            $rows[] = $resultRecord;
        }

        $event = new SearchResultAfter($this->datagrid, $rows, $this->searchQuery);
        $this->dispatcher->dispatch(SearchResultAfter::NAME, $event);

        return $event->getRecords();
    }

    /**
     * @return SearchQueryInterface
     */
    public function getSearchQuery()
    {
        return $this->searchQuery;
    }
}
