<?php

namespace Oro\Bundle\BatchBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

/**
 * Implements Iteration Strategy by modifying Query with Sql Walkers
 */
class IdentifierIterationStrategy implements IdentityIterationStrategyInterface
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
