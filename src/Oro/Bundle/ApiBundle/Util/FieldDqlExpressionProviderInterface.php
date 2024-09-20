<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;

/**
 * Represents a service that provides a DQL expression for an entity field
 * that should be used in WHERE and ORDER BY clauses.
 */
interface FieldDqlExpressionProviderInterface
{
    public function getFieldDqlExpression(QueryBuilder $qb, string $fieldPath): ?string;
}
