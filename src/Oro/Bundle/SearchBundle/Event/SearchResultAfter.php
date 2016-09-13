<?php

namespace Oro\Bundle\SearchBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchResultAfter extends OrmResultAfter
{
    const NAME = 'oro_datagrid.search_datasource.result.after';

    /**
     * @var SearchQueryInterface
     */
    protected $query;

    /**
     * @param DatagridInterface         $datagrid
     * @param array                     $records
     * @param SearchQueryInterface|null $query
     */
    public function __construct(
        DatagridInterface $datagrid,
        array $records = [],
        SearchQueryInterface $query = null
    ) {
        $this->datagrid = $datagrid;
        $this->records  = $records;
        $this->query    = $query;
    }
}
