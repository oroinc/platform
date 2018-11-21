<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
use Oro\Component\PhpUtils\ReflectionUtil;

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

    /**
     * @param CountQueryBuilderOptimizer $countQueryOptimizer
     * @param QueryResolver              $queryResolver
     */
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
    private function executeTotalCountCallback($callback)
    {
        if (!\is_callable($callback)) {
            throw new \RuntimeException(\sprintf(
                'Expected callable for "totalCount", "%s" given.',
                \is_object($callback) ? \get_class($callback) : gettype($callback)
            ));
        }

        $totalCount = \call_user_func($callback);
        if (!\is_int($totalCount)) {
            throw new \RuntimeException(\sprintf(
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
    private function calculateTotalCount($query, EntityDefinitionConfig $config = null)
    {
        if ($query instanceof QueryBuilder) {
            $countQuery = $this->countQueryBuilderOptimizer
                ->getCountQueryBuilder($query)
                ->getQuery()
                ->setMaxResults(null)
                ->setFirstResult(null);
            $this->resolveQuery($countQuery, $config);
        } elseif ($query instanceof Query) {
            $countQuery = $this->cloneQuery($query)
                ->setMaxResults(null)
                ->setFirstResult(null);
            $this->resolveQuery($countQuery, $config);
        } elseif ($query instanceof SqlQueryBuilder) {
            $countQuery = $this->cloneQuery($query)
                ->setMaxResults(null)
                ->setFirstResult(null)
                ->getQuery();
        } elseif ($query instanceof SqlQuery) {
            $countQuery = $this->cloneQuery($query)
                ->getQueryBuilder()
                ->setMaxResults(null)
                ->setFirstResult(null);
        } else {
            throw new \RuntimeException(\sprintf(
                'Expected instance of Doctrine\ORM\QueryBuilder, Doctrine\ORM\Query'
                . ', Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder'
                . ' or Oro\Bundle\EntityBundle\ORM\SqlQuery, "%s" given.',
                \is_object($query) ? \get_class($query) : gettype($query)
            ));
        }

        return $this->executeCountQuery($countQuery);
    }

    /**
     * @param $countQuery
     *
     * @return int
     */
    private function executeCountQuery($countQuery)
    {
        if ($countQuery instanceof Query) {
            $paginator = new Paginator($countQuery);
            $paginator->setUseOutputWalkers(false);

            // the result-set mapping is not relevant and should be rebuilt
            // because the query will be changed by Doctrine\ORM\Tools\Pagination\Paginator
            // unfortunately the reflection is the only way to clear the result-set mapping
            $resultSetMappingProperty = ReflectionUtil::getProperty(
                new \ReflectionClass($countQuery),
                '_resultSetMapping'
            );
            if (null === $resultSetMappingProperty) {
                throw new \LogicException(sprintf(
                    'The "_resultSetMapping" property does not exist in %s.',
                    get_class($countQuery)
                ));
            }
            $resultSetMappingProperty->setAccessible(true);
            $resultSetMappingProperty->setValue($countQuery, null);

            return $paginator->count();
        }

        return QueryCountCalculator::calculateCount($countQuery, false);
    }

    /**
     * @param Query                       $query
     * @param EntityDefinitionConfig|null $config
     */
    private function resolveQuery(Query $query, EntityDefinitionConfig $config = null)
    {
        if (null !== $config) {
            $this->queryResolver->resolveQuery($query, $config);
        }
    }

    /**
     * Makes full clone of the given query, including its parameters and hints
     *
     * @param object $query
     *
     * @return object
     */
    private function cloneQuery($query)
    {
        if ($query instanceof Query) {
            return QueryUtil::cloneQuery($query);
        }

        return clone $query;
    }
}
