<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\GridEventInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Symfony\Component\EventDispatcher\Event;

class SearchResultBefore extends Event implements GridEventInterface
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
    public function __construct(DatagridInterface $datagrid, SearchQueryInterface $query)
    {
        $this->datagrid = $datagrid;
        $this->query    = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatagrid()
    {
        return $this->datagrid;
    }

    /**
     * @return SearchQueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }
}
