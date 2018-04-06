<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Datasource;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    public function __construct(QueryFactoryInterface $factory, EventDispatcherInterface $eventDispatcher)
    {
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

        $this->searchQuery = $this->queryFactory->create($config);

        $this->yamlToSearchQueryConverter->process($this->searchQuery, $config);

        $grid->setDatasource(clone $this);
    }

    /**
     * {@inheritDoc}
     */
    public function getResults()
    {
        $event = new SearchResultBefore($this->datagrid, $this->searchQuery);
        $this->dispatcher->dispatch(SearchResultBefore::NAME, $event);

        $results = $this->searchQuery->execute();

        $rows = [];
        foreach ($results as $result) {
            $resultRecord = new ResultRecord($result);
            $resultRecord->addData(
                array_merge(['id' => $result->getId()], $result->getSelectedData())
            );
            $rows[] = $resultRecord;
        }

        $event = new SearchResultAfter($this->datagrid, $this->searchQuery, $rows);
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
