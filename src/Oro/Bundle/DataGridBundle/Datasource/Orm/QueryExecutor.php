<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

/**
 * The default implementation of QueryExecutorInterface.
 */
class QueryExecutor implements QueryExecutorInterface
{
    /**
     * {@inheritdoc}
     */
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
