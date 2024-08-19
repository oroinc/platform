<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship\GetRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQuery;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Oro\Component\EntitySerializer\QueryResolver;

/**
 * Calculates the total number of records and sets it
 * to "X-Include-Total-Count" response header
 * if it was requested by "X-Include: totalCount" request header.
 */
class SetTotalCountHeader implements ProcessorInterface
{
    public const RESPONSE_HEADER_NAME = 'X-Include-Total-Count';
    public const REQUEST_HEADER_VALUE = 'totalCount';

    private CountQueryBuilderOptimizer $countQueryBuilderOptimizer;
    private QueryResolver $queryResolver;

    public function __construct(
        CountQueryBuilderOptimizer $countQueryOptimizer,
        QueryResolver $queryResolver
    ) {
        $this->countQueryBuilderOptimizer = $countQueryOptimizer;
        $this->queryResolver = $queryResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext|GetRelationshipContext|GetSubresourceContext $context */

        if ($context->getResponseHeaders()->has(self::RESPONSE_HEADER_NAME)) {
            // total count header is already set
            return;
        }

        $xInclude = $context->getRequestHeaders()->get(Context::INCLUDE_HEADER);
        if (empty($xInclude) || !\in_array(self::REQUEST_HEADER_VALUE, $xInclude, true)) {
            // total count is not requested
            return;
        }

        $totalCount = $this->getTotalCount($context, $context->getTotalCountCallback());
        if (null !== $totalCount) {
            $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, $totalCount);
        }
    }

    private function getTotalCount(Context $context, ?callable $totalCountCallback): ?int
    {
        if (null !== $totalCountCallback) {
            return $this->executeTotalCountCallback($totalCountCallback);
        }

        if ($context->getAction() !== ApiAction::DELETE_LIST && $context->getConfig()?->getPageSize() === -1) {
            // the paging is disabled, no need to execute a separate DB query to calculate total count
            return $this->calculateResultCount($context);
        }

        $query = $context->getQuery();
        if (null !== $query) {
            return $this->calculateTotalCount($query, $context->getConfig());
        }

        return $this->calculateResultCount($context);
    }

    private function executeTotalCountCallback(callable $callback): int
    {
        $totalCount = $callback();
        if (!\is_int($totalCount)) {
            throw new \RuntimeException(sprintf(
                'Expected integer as result of "totalCount" callback, "%s" given.',
                get_debug_type($totalCount)
            ));
        }

        return $totalCount;
    }

    private function calculateResultCount(Context $context): ?int
    {
        if (!$context->hasResult()) {
            return null;
        }

        $data = $context->getResult();
        if (!\is_array($data)) {
            return null;
        }

        return \count($data);
    }

    private function calculateTotalCount(mixed $query, ?EntityDefinitionConfig $config): int
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
            throw new \RuntimeException(sprintf(
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
