<?php

namespace Oro\Bundle\DataGridBundle\Event;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

class OrmResultBeforeQuery extends Event implements GridEventInterface
{
    const NAME = 'oro_datagrid.orm_datasource.result.before_query';

    /** @var DatagridInterface */
    protected $datagrid;

    /** @var QueryBuilder */
    protected $qb;

    /**
     * @param DatagridInterface $datagrid
     * @param QueryBuilder $qb
     */
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
