<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Extension\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Query;

class SearchResultsGridListener
{
    /** @var string */
    protected $paramName;

    /**
     * Adjust query for tag-results-grid (tag search result grid)
     * after datasource has been built
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();

        if ($datasource instanceof SearchDatasource) {
            $parameters   = $datagrid->getParameters();
            $searchEntity = $parameters->get('from', '*');
            $searchEntity = empty($searchEntity) ? '*' : $searchEntity;
            $searchString = $parameters->get('search', '');

            $datasource->getQuery()
                ->from($searchEntity)
                ->andWhere(Indexer::TEXT_ALL_DATA_FIELD, Query::OPERATOR_CONTAINS, $searchString, Query::TYPE_TEXT);
        }
    }
}
