<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Symfony\Contracts\EventDispatcher\Event;

class OrmResultBeforeQuery extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.orm_datasource.result.before_query';

    /** @var DatagridInterface */
    protected $datagrid;

    /** @var QueryBuilder */
    protected $qb;

    public function __construct(DatagridInterface $datagrid, QueryBuilder $qb)
    {
        $this->datagrid = $datagrid;
        $this->qb = $qb;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatagrid()
    {
        return $this->datagrid;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }
}
