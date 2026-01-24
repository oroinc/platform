<?php

namespace Oro\Bundle\BatchBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

/**
 * Iteration strategy that loads entity identifiers without `ORDER BY` clauses for efficient pagination.
 *
 * This strategy optimizes large result set iteration by using a two-query approach:
 * 1. First query loads all entity identifiers using a custom {@see IdentifierHydrator}, with `ORDER BY` clauses
 *    removed to improve performance ({@see ReduceOrderByWalker}) and only selecting identifier fields
 *    ({@see SelectIdentifierWalker}).
 * 2. Second query loads the actual entity data in pages, using the previously loaded identifiers
 *    to filter results ({@see LimitIdentifierWalker}) instead of relying on `LIMIT`/`OFFSET`, which can be
 *    inefficient with large offsets.
 *
 * This approach is particularly effective for large datasets where OFFSET-based pagination becomes
 * a performance bottleneck, as it avoids scanning and discarding large numbers of rows.
 */
class IdentifierWithoutOrderByIterationStrategy implements IdentityIterationStrategyInterface
{
    #[\Override]
    public function initializeIdentityQuery(Query $query)
    {
        $identifierHydrationMode = 'IdentifierHydrator';
        $query
            ->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode($identifierHydrationMode, IdentifierHydrator::class);

        $query->setHydrationMode($identifierHydrationMode);

        QueryUtil::addTreeWalker($query, ReduceOrderByWalker::class);
        QueryUtil::addTreeWalker($query, SelectIdentifierWalker::class);
    }

    #[\Override]
    public function initializeDataQuery(Query $query)
    {
        QueryUtil::addTreeWalker($query, LimitIdentifierWalker::class);

        // limit and offset implemented with ids
        $query->setFirstResult(null);
        $query->setMaxResults(null);
    }

    #[\Override]
    public function setDataQueryIdentifiers(Query $query, array $identifiers)
    {
        $query->setParameter(LimitIdentifierWalker::PARAMETER_IDS, $identifiers);
    }
}
