<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\GridEventInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Symfony\Component\EventDispatcher\Event;

class SearchResultAfter extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.search_datasource.result.after';

    /**
     * @var DatagridInterface
     */
    protected $datagrid;

    /**
     * @var SearchQueryInterface
     */
    protected $query;

    /**
     * @var ResultRecordInterface[]
     */
    protected $records;

    /**
     * @param DatagridInterface    $datagrid
     * @param SearchQueryInterface $query
     * @param array                $records
     */
    public function __construct(DatagridInterface $datagrid, SearchQueryInterface $query, array $records)
    {
        $this->datagrid = $datagrid;
        $this->query    = $query;
        $this->records  = $records;
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

    /**
     * @return ResultRecordInterface[]
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @param array $records
     */
    public function setRecords(array $records)
    {
        $this->records = $records;
    }
}
