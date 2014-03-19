<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Class ResultAfter
 * @package Oro\Bundle\DataGridBundle\Event
 *
 * This event dispatched after datagrid builder finish building result
 */
class ResultAfter extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.datgrid.result.after';

    /** @var DatagridInterface */
    protected $datagrid;

    /** @var ResultRecordInterface[] */
    protected $records;

    public function __construct(DatagridInterface $datagrid, array $records = array())
    {
        $this->datagrid = $datagrid;
        $this->records  = $records;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatagrid()
    {
        return $this->datagrid;
    }

    /**
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }
}
