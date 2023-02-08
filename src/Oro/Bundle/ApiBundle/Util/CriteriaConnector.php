<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Criteria as CommonCriteria;
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
    private EntityClassResolver $entityClassResolver;

    public function __construct(
        CriteriaNormalizer $criteriaNormalizer,
        CriteriaPlaceholdersResolver $placeholdersResolver,
        QueryExpressionVisitorFactory $expressionVisitorFactory,
        EntityClassResolver $entityClassResolver
    ) {
        $this->criteriaNormalizer = $criteriaNormalizer;
        $this->placeholdersResolver = $placeholdersResolver;
        $this->expressionVisitorFactory = $expressionVisitorFactory;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * Adds the given criteria to the query builder.
     */
    public function applyCriteria(QueryBuilder $qb, CommonCriteria $criteria): void
    {
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($qb);
        if ($criteria instanceof Criteria) {
            $rootEntityClass = $this->entityClassResolver->getEntityClass($qb->getRootEntities()[0]);
            $this->criteriaNormalizer->normalizeCriteria($criteria, $rootEntityClass);
            $this->placeholdersResolver->resolvePlaceholders($criteria, $rootAlias);
            $this->addJoins($qb, $criteria);
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

    private function addJoins(QueryBuilder $qb, Criteria $criteria): void
    {
        $joins = $criteria->getJoins();
        if (!empty($joins)) {
            foreach ($joins as $join) {
                $method = strtolower($join->getJoinType()) . 'Join';
                $qb->{$method}(
                    $join->getJoin(),
                    $join->getAlias(),
                    $join->getConditionType(),
                    $join->getCondition(),
                    $join->getIndexBy()
                );
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
            foreach ($aliases as $alias) {
                if ($sort !== $alias && str_starts_with($sort . '.', $alias . '.')) {
                    $hasValidAlias = true;
                    break;
                }
            }

            if (!$hasValidAlias) {
                $sort = $aliases[0] . '.' . $sort;
            }

            QueryBuilderUtil::checkField($sort);
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
