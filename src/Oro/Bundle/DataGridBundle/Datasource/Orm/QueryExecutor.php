<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\ORM\Walker\MaterializedViewOutputResultModifier;

/**
 * The default implementation of QueryExecutorInterface.
 */
class QueryExecutor implements QueryExecutorInterface
{
    #[\Override]
    public function execute(DatagridInterface $datagrid, Query $query, $executeFunc = null)
    {
        // Added to fix
        // Doctrine\DBAL\Exception\DriverException: An exception occurred while executing a query:
        // SQLSTATE[08P01]: <<Unknown error>>: 7 ERROR:  bind message supplies 2 parameters,
        // but prepared statement "" requires 0
        //
        // When exporting via materialized view, we must clear parameters & mappings before Doctrine executes
        // to avoid QueryException about mismatched parameter counts (Doctrine ORM now validates before walkers).
        if ($query->getHint(MaterializedViewOutputResultModifier::USE_MATERIALIZED_VIEW)) {
            $parserResult = QueryUtil::parseQuery($query); // ensure parserResult exists
            QueryUtil::resetParameters($query, $parserResult); // clear both parameters and mappings
            // Prevent second parse that would restore parameter mappings by syncing parsedTypes with empty parameters.
            \Closure::bind(static function (Query $q) {
                $q->parsedTypes = []; // match current empty parameters set
            }, null, $query)($query);
        }

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
