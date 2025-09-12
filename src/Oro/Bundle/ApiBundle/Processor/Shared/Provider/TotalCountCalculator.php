<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Provider;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQuery;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Oro\Component\EntitySerializer\QueryResolver;

/**
 * Provides a set of methods that helps calculation of the total number of records.
 */
class TotalCountCalculator
{
    public function __construct(
        private readonly CountQueryBuilderOptimizer $countQueryBuilderOptimizer,
        private readonly QueryResolver $queryResolver
    ) {
    }

    public function executeTotalCountCallback(callable $callback): int
    {
        $totalCount = $callback();
        if (!\is_int($totalCount)) {
            throw new \RuntimeException(\sprintf(
                'Expected integer as result of "totalCount" callback, "%s" given.',
                get_debug_type($totalCount)
            ));
        }

        return $totalCount;
    }

    public function calculateTotalCount(mixed $query, ?EntityDefinitionConfig $config): int
    {
        $useCountDistinct = false;
        if ($query instanceof QueryBuilder) {
            $useCountDistinct = !$this->hasComputedFields($query);
            $countQuery = $this->countQueryBuilderOptimizer
                ->getCountQueryBuilder($query)
                ->getQuery()
                ->setMaxResults(null)
                ->setFirstResult(null);
            $this->resolveQuery($countQuery, $config);
        } elseif ($query instanceof Query) {
            $useCountDistinct = true;
            $countQuery = QueryUtil::cloneQuery($query)
                ->setMaxResults(null)
                ->setFirstResult(null);
            $this->resolveQuery($countQuery, $config);
        } elseif ($query instanceof SqlQueryBuilder) {
            $countQuery = (clone $query)
                ->setMaxResults(null)
                ->setFirstResult(null)
                ->getQuery();
        } elseif ($query instanceof SqlQuery) {
            $countQuery = (clone $query)
                ->getQueryBuilder()
                ->setMaxResults(null)
                ->setFirstResult(null);
        } else {
            throw new \RuntimeException(\sprintf(
                'Expected instance of %s, %s, %s or %s, "%s" given.',
                QueryBuilder::class,
                Query::class,
                SqlQueryBuilder::class,
                SqlQuery::class,
                get_debug_type($query)
            ));
        }

        if ($useCountDistinct) {
            return QueryCountCalculator::calculateCountDistinct($countQuery);
        }

        return QueryCountCalculator::calculateCount($countQuery);
    }

    private function resolveQuery(Query $query, ?EntityDefinitionConfig $config): void
    {
        if (null !== $config) {
            $this->queryResolver->resolveQuery($query, $config);
        }
    }

    private function hasComputedFields(QueryBuilder $query): bool
    {
        /** @var Expr\Select[] $selectPart */
        $selectPart = $query->getDQLPart('select');
        foreach ($selectPart as $select) {
            foreach ($select->getParts() as $part) {
                if (preg_match('/.+ AS [\w\-]+$/i', $part) === 1) {
                    return true;
                }
            }
        }

        return false;
    }
}
