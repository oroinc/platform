<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Criteria as CommonCriteria;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitorFactory;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Helps to apply criteria stored in Criteria object to the QueryBuilder.
 */
class CriteriaConnector
{
    private CriteriaNormalizer $criteriaNormalizer;
    private CriteriaPlaceholdersResolver $placeholdersResolver;
    private QueryExpressionVisitorFactory $expressionVisitorFactory;
    private FieldDqlExpressionProviderInterface $fieldDqlExpressionProvider;
    private EntityClassResolver $entityClassResolver;

    public function __construct(
        CriteriaNormalizer $criteriaNormalizer,
        CriteriaPlaceholdersResolver $placeholdersResolver,
        QueryExpressionVisitorFactory $expressionVisitorFactory,
        FieldDqlExpressionProviderInterface $fieldDqlExpressionProvider,
        EntityClassResolver $entityClassResolver
    ) {
        $this->criteriaNormalizer = $criteriaNormalizer;
        $this->placeholdersResolver = $placeholdersResolver;
        $this->expressionVisitorFactory = $expressionVisitorFactory;
        $this->fieldDqlExpressionProvider = $fieldDqlExpressionProvider;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * Adds the given criteria to the query builder.
     */
    public function applyCriteria(QueryBuilder $qb, CommonCriteria $criteria): void
    {
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($qb);
        if ($criteria instanceof Criteria) {
            $rootEntityClass = $this->entityClassResolver->getEntityClass(QueryBuilderUtil::getSingleRootEntity($qb));
            $this->addJoinsToCriteria($criteria, $qb, $rootAlias);
            $this->criteriaNormalizer->normalizeCriteria($criteria, $rootEntityClass);
            $this->placeholdersResolver->resolvePlaceholders($criteria, $rootAlias);
            $this->processJoins($qb, $criteria, $rootAlias);
        } else {
            $this->placeholdersResolver->resolvePlaceholders($criteria, $rootAlias);
        }
        $this->addCriteria($qb, $criteria);
    }

    /**
     * Adds criteria to the query.
     * This is a copy of QueryBuilder addCriteria method. We should set another QueryExpressionVisitor that is able
     * to add own comparison or composite expressions.
     */
    private function addCriteria(QueryBuilder $qb, CommonCriteria $criteria): void
    {
        $aliases = $qb->getAllAliases();
        $this->processWhere($qb, $criteria, $aliases);
        $this->processOrderings($qb, $criteria, $aliases);

        // Overwrite limits only if they was set in criteria
        $firstResult = $criteria->getFirstResult();
        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }
        $maxResults = $criteria->getMaxResults();
        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }
    }

    private function processJoins(QueryBuilder $qb, Criteria $criteria, string $rootAlias): void
    {
        $joins = $criteria->getJoins();
        if ($joins) {
            $this->resetJoins($qb, $rootAlias);
            $this->addJoins($qb, $joins);
        }
    }

    private function resetJoins(QueryBuilder $qb, string $rootAlias): void
    {
        $joinPart = $qb->getDQLPart('join');
        if (!$joinPart) {
            return;
        }

        $qb->resetDQLPart('join');
        foreach ($joinPart as $joinGroupAlias => $joins) {
            if ($joinGroupAlias !== $rootAlias) {
                $this->addJoins($qb, $joins);
            }
        }
    }

    private function addJoins(QueryBuilder $qb, array $joins): void
    {
        /** @var Expr\Join $join */
        foreach ($joins as $join) {
            QueryBuilderUtil::addJoin($qb, $join);
        }
    }

    private function addJoinsToCriteria(Criteria $criteria, QueryBuilder $qb, string $rootAlias): void
    {
        $joinPart = $qb->getDQLPart('join');
        /** @var Expr\Join[] $joins */
        foreach ($joinPart as $joinGroupAlias => $joins) {
            if ($joinGroupAlias !== $rootAlias) {
                continue;
            }
            foreach ($joins as $join) {
                $joinAlias = $join->getAlias();
                if (!$criteria->hasJoin($joinAlias)) {
                    $method = 'add' . ucfirst(strtolower($join->getJoinType())) . 'Join';
                    $criteria
                        ->{$method}(
                            $joinAlias,
                            $join->getJoin(),
                            $join->getConditionType(),
                            $join->getCondition(),
                            $join->getIndexBy()
                        )
                        ->setAlias($joinAlias);
                }
            }
        }
    }

    private function processWhere(QueryBuilder $qb, CommonCriteria $criteria, array $aliases): void
    {
        $whereExpression = $criteria->getWhereExpression();
        if (null !== $whereExpression) {
            $expressionVisitor = $this->expressionVisitorFactory->createExpressionVisitor();
            $expressionVisitor->setQueryAliases($aliases);
            $expressionVisitor->setQueryJoinMap($this->getJoinMap($criteria));
            $expressionVisitor->setQuery($qb);
            $qb->andWhere($expressionVisitor->dispatch($whereExpression));
            $parameters = $expressionVisitor->getParameters();
            foreach ($parameters as $parameter) {
                $qb->getParameters()->add($parameter);
            }
        }
    }

    private function processOrderings(QueryBuilder $qb, CommonCriteria $criteria, array $aliases): void
    {
        $orderings = $criteria->getOrderings();
        foreach ($orderings as $sort => $order) {
            $hasValidAlias = false;
            if (str_contains($sort, '.')) {
                foreach ($aliases as $alias) {
                    if ($sort !== $alias && str_starts_with($sort . '.', $alias . '.')) {
                        $hasValidAlias = true;
                        break;
                    }
                }
            }
            if (!$hasValidAlias) {
                if (str_starts_with($sort, Criteria::PLACEHOLDER_START)
                    && str_ends_with($sort, Criteria::PLACEHOLDER_END)
                ) {
                    // it is a computed field that does not related to any join or a root entity
                    $sort = substr($sort, 1, -1);
                } else {
                    $sort = $aliases[0] . '.' . $sort;
                }
            }
            QueryBuilderUtil::checkField($sort);
            $sort = $this->fieldDqlExpressionProvider->getFieldDqlExpression($qb, $sort) ?? $sort;
            $qb->addOrderBy($sort, QueryBuilderUtil::getSortOrder($order));
        }
    }

    /**
     * @param CommonCriteria $criteria
     *
     * @return array [path => join alias, ...]
     */
    private function getJoinMap(CommonCriteria $criteria): array
    {
        $map = [];
        if ($criteria instanceof Criteria) {
            $joins = $criteria->getJoins();
            foreach ($joins as $path => $join) {
                $map[$path] = $join->getAlias();
            }
        }

        return $map;
    }
}
