<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after executing an ORM query in a datagrid datasource.
 *
 * This event allows listeners to modify or process the result records after the query has been
 * executed but before the results are returned to the datagrid. This is useful for post-processing
 * data, adding computed fields, or filtering results based on business logic.
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

    public function __construct(
        DatagridInterface $datagrid,
        array $records = [],
        ?AbstractQuery $query = null
    ) {
        $this->datagrid = $datagrid;
        $this->records  = $records;
        $this->query    = $query;
    }

    #[\Override]
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

    public function setRecords(array $records)
    {
        $this->records = $records;
    }

    /**
     * @return AbstractQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
