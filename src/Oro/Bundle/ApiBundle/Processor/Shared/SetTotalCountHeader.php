<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ListContext;
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

    /** @var CountQueryBuilderOptimizer */
    private $countQueryBuilderOptimizer;

    /** @var QueryResolver */
    private $queryResolver;

    public function __construct(
        CountQueryBuilderOptimizer $countQueryOptimizer,
        QueryResolver $queryResolver
    ) {
        $this->countQueryBuilderOptimizer = $countQueryOptimizer;
        $this->queryResolver = $queryResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ListContext $context */

        if ($context->getResponseHeaders()->has(self::RESPONSE_HEADER_NAME)) {
            // total count header is already set
            return;
        }

        $xInclude = $context->getRequestHeaders()->get(Context::INCLUDE_HEADER);
        if (empty($xInclude) || !\in_array(self::REQUEST_HEADER_VALUE, $xInclude, true)) {
            // total count is not requested
            return;
        }

        $totalCount = null;

        $totalCountCallback = $context->getTotalCountCallback();
        if (null !== $totalCountCallback) {
            $totalCount = $this->executeTotalCountCallback($totalCountCallback);
        }

        $query = $context->getQuery();
        if (null !== $query && null === $totalCount) {
            $totalCount = $this->calculateTotalCount($query, $context->getConfig());
        }

        if (null !== $totalCount) {
            $context->getResponseHeaders()->set(self::RESPONSE_HEADER_NAME, $totalCount);
        }
    }

    /**
     * @param callable $callback
     *
     * @return int
     */
    private function executeTotalCountCallback($callback): int
    {
        if (!\is_callable($callback)) {
            throw new \RuntimeException(sprintf(
                'Expected callable for "totalCount", "%s" given.',
                \is_object($callback) ? \get_class($callback) : gettype($callback)
            ));
        }

        $totalCount = $callback();
        if (!\is_int($totalCount)) {
            throw new \RuntimeException(sprintf(
                'Expected integer as result of "totalCount" callback, "%s" given.',
                \is_object($totalCount) ? \get_class($totalCount) : gettype($totalCount)
            ));
        }

        return $totalCount;
    }

    /**
     * @param mixed                       $query
     * @param EntityDefinitionConfig|null $config
     *
     * @return int
     */
    private function calculateTotalCount($query, ?EntityDefinitionConfig $config): int
    {
        if ($query instanceof QueryBuilder) {
            $countQuery = $this->countQueryBuilderOptimizer
                ->getCountQueryBuilder($query)
                ->getQuery()
                ->setMaxResults(null)
                ->setFirstResult(null);
            $this->resolveQuery($countQuery, $config);
        } elseif ($query instanceof Query) {
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
                \is_object($query) ? \get_class($query) : gettype($query)
            ));
        }

        if ($countQuery instanceof Query) {
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
}
