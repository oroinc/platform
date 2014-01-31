<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\RequestParameters;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Extension\SearchDatasource;

class SearchResultsGridListener
{
    /** @var  RequestParameters */
    protected $requestParams;

    /** @var string */
    protected $paramName;

    /**
     * @param RequestParameters $requestParams
     */
    public function __construct(RequestParameters $requestParams)
    {
        $this->requestParams = $requestParams;
    }

    /**
     * Adjust query for tag-results-grid (tag search result grid)
     * after datasource has been built
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();
        if ($datasource instanceof SearchDatasource) {
            $searchEntity = $this->requestParams->get('from', '*');
            $searchEntity = empty($searchEntity) ? '*' : $searchEntity;

            $searchString = $this->requestParams->get('search', '');

            $datasource->getQuery()
                ->from($searchEntity)
                ->andWhere(Indexer::TEXT_ALL_DATA_FIELD, '~', $searchString, 'text');
        }
    }
}
