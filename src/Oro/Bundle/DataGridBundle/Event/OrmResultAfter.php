<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\AbstractQuery;

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
     * @var AbstractQuery
     */
    protected $query;

    /**
     * @param DatagridInterface $datagrid
     * @param array             $records
     * @param AbstractQuery     $query
     */
    public function __construct(DatagridInterface $datagrid, array $records = array(), AbstractQuery $query = null)
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
     * @return AbstractQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
