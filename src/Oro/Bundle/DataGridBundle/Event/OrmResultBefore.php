<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\AbstractQuery;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

/**
 * Class ResultBefore
 * @package Oro\Bundle\DataGridBundle\Event
 *
 * This event is dispatched before datagrid builder starts to build result
 */
class OrmResultBefore extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.orm_datasource.result.before';

    /**
     * @var DatagridInterface
     */
    protected $datagrid;

    /**
     * @var AbstractQuery
     */
    protected $query;

    /**
     * @param DatagridInterface $datagrid
     * @param AbstractQuery     $query
     */
    public function __construct(DatagridInterface $datagrid, AbstractQuery $query)
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
     * @return AbstractQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
