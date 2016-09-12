<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchResultBefore extends Event
{
    const NAME = 'oro_datagrid.search_datasource.result.before';

    /**
     * @var DatagridInterface
     */
    protected $datagrid;

    /**
     * @var SearchQueryInterface
     */
    protected $query;

    /**
     * @param DatagridInterface    $datagrid
     * @param SearchQueryInterface $query
     */
    public function __construct(
        DatagridInterface $datagrid,
        SearchQueryInterface $query
    ) {
        $this->datagrid = $datagrid;
        $this->query    = $query;
    }

    /**
     * @return SearchQueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }
}
