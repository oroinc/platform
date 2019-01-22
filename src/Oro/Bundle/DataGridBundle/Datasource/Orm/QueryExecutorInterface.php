<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

/**
 * An interface for classes responsible to execute ORM datagrid queries.
 */
interface QueryExecutorInterface
{
    /**
     * Executes the given ORM query related to the given datagrid.
     * A function specified
     * If $executeFunc parameter has a function it is used to execute the query;
     * otherwise, the "execute()" method of the query is used.
     *
     * @param DatagridInterface $datagrid
     * @param Query             $query
     * @param callable|null     $executeFunc function (Query $query): mixed
     *
     * @return mixed
     */
    public function execute(DatagridInterface $datagrid, Query $query, $executeFunc = null);
}
