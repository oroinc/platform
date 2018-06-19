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
    /** @var QueryHintResolverInterface */
    private $queryHintResolver;

    /**
     * @param QueryHintResolverInterface $queryHintResolver
     */
    public function __construct(QueryHintResolverInterface $queryHintResolver)
    {
        $this->queryHintResolver = $queryHintResolver;
    }

    /**
     * @param Query        $query
     * @param EntityConfig $config
     */
    public function resolveQuery(Query $query, EntityConfig $config)
    {
        $this->queryHintResolver->resolveHints($query, $config->getHints());
    }
}
