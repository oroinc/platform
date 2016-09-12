<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\AbstractQuery;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\SearchBundle\Extension\SearchQueryInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Class ResultAfter
 * @package Oro\Bundle\DataGridBundle\Event
 *
 * This event dispatched after datagrid builder finish building result
 */
class GridResultAfter extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.orm_datasource.result.after';

    /**
     * @var DatagridInterface
     */
    protected $datagrid;

    /**
     * @var ResultRecordInterface[]
     */
    protected $records;

    /**
     * @var SearchQueryInterface|AbstractQuery
     */
    protected $query;

    /**
     * @param DatagridInterface    $datagrid
     * @param array                $records
     * @param SearchQueryInterface $query
     */
    public function __construct(
        DatagridInterface $datagrid,
        array $records = [],
        $query = null
    ) {
        $this->datagrid = $datagrid;
        $this->records  = $records;
        $this->query    = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatagrid()
    {
        return $this->datagrid;
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

    /**
     * @return SearchQueryInterface|AbstractQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
