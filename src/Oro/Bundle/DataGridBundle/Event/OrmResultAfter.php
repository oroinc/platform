<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\Query;
use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Class ResultAfter
 * @package Oro\Bundle\DataGridBundle\Event
 *
 * This event dispatched after datagrid builder finish building result
 */
class OrmResultAfter extends Event implements GridEventInterface
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
     * @var Query
     */
    protected $query;

    /**
     * @param DatagridInterface $datagrid
     * @param array             $records
     * @param Query             $query
     */
    public function __construct(DatagridInterface $datagrid, array $records = array(), Query $query = null)
    {
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
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }
}
