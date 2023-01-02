<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\Query;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

/**
 * The utility class that is used by the entity serializer to final update ORM queries
 * before they are executed.
 */
class QueryResolver
{
    private QueryHintResolverInterface $queryHintResolver;

    public function __construct(QueryHintResolverInterface $queryHintResolver)
    {
        $this->queryHintResolver = $queryHintResolver;
    }

    public function resolveQuery(Query $query, EntityConfig $config): void
    {
        $this->queryHintResolver->resolveHints($query, $config->getHints());

        // use HINT_REFRESH to prevent collisions between queries for the same entity
        // but with different partial fields
        if (!$query->hasHint(Query::HINT_REFRESH)) {
            $query->setHint(Query::HINT_REFRESH, true);
        } elseif (false === $query->getHint(Query::HINT_REFRESH)) {
            /**
             * Doctrine considers any value except NULL as enabled HINT_REFRESH hint
             * @see \Doctrine\ORM\UnitOfWork::createEntity
             */
            $query->setHint(Query::HINT_REFRESH, null);
        }
    }
}
