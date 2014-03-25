<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\Query;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

/**
 * Class ResultBefore
 * @package Oro\Bundle\DataGridBundle\Event
 *
 * This event dispatched before datagrid builder starts build result
 */
class OrmResultBefore extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.orm_datasource.result.before';

    /**
     * @var DatagridInterface
     */
    protected $datagrid;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @param DatagridInterface $datagrid
     * @param Query             $query
     */
    public function __construct(DatagridInterface $datagrid, Query $query)
    {
        $this->datagrid = $datagrid;
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
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }
}
