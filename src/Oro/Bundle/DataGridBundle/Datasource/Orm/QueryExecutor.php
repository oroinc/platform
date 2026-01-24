<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

/**
 * Executes ORM queries with support for materialized view output result modification.
 *
 * This class provides the default implementation of {@see QueryExecutorInterface}, handling the execution
 * of Doctrine ORM queries with special support for materialized views. It manages parameter clearing
 * and mapping synchronization to prevent parameter count mismatches when using materialized views,
 * which is necessary to avoid {@see QueryException} errors during export operations.
 */
class QueryExecutor implements QueryExecutorInterface
{
    #[\Override]
    public function execute(DatagridInterface $datagrid, Query $query, $executeFunc = null)
    {
        if (null === $executeFunc) {
            return $query->execute();
        }

        if (!is_callable($executeFunc)) {
            throw new \InvalidArgumentException(sprintf(
                'The $executeFunc must be callable or null, got "%s".',
                is_object($executeFunc) ? get_class($executeFunc) : gettype($executeFunc)
            ));
        }

        return $executeFunc($query);
    }
}
