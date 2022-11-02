<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\ORM\Query;

/**
 * An interface for resolvers of query hints
 */
interface QueryHintResolverInterface
{
    /**
     * Resolves query hints
     */
    public function resolveHints(Query $query, array $hints = []);
}
