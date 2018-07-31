<?php

namespace Oro\Bundle\ApiBundle\Util;

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
    /** @var CriteriaNormalizer */
    private $criteriaNormalizer;

    /** @var CriteriaPlaceholdersResolver */
    private $placeholdersResolver;

    /** @var QueryExpressionVisitorFactory */
    private $expressionVisitorFactory;

    /** @var EntityClassResolver */
    private $entityClassResolver;

    /**
     * @param CriteriaNormalizer            $criteriaNormalizer
     * @param CriteriaPlaceholdersResolver  $placeholdersResolver
     * @param QueryExpressionVisitorFactory $expressionVisitorFactory
     * @param EntityClassResolver           $entityClassResolver
     */
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
     *
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     */
    public function applyCriteria(QueryBuilder $qb, Criteria $criteria): void
    {
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($qb);
        $rootEntityClass = $this->entityClassResolver->getEntityClass($qb->getRootEntities()[0]);
        $this->criteriaNormalizer->normalizeCriteria($criteria, $rootEntityClass);
        $this->placeholdersResolver->resolvePlaceholders($criteria, $rootAlias);

        $joins = $criteria->getJoins();
        if (!empty($joins)) {
            foreach ($joins as $join) {
                $method = \strtolower($join->getJoinType()) . 'Join';
                $qb->{$method}(
                    $join->getJoin(),
                    $join->getAlias(),
                    $join->getConditionType(),
                    $join->getCondition(),
                    $join->getIndexBy()
                );
            }
        }

        $this->addCriteria($qb, $criteria);
    }

    /**
     * Adds criteria to the query.
     * This is a copy of QueryBuilder addCriteria method. We should set another QueryExpressionVisitor that is able
     * to add own comparison or composite expressions.
     *
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     */
    private function addCriteria(QueryBuilder $qb, Criteria $criteria): void
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

    /**
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     * @param array        $aliases
     */
    private function processWhere(QueryBuilder $qb, Criteria $criteria, array $aliases): void
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

    /**
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     * @param array        $aliases
     */
    private function processOrderings(QueryBuilder $qb, Criteria $criteria, array $aliases): void
    {
        $orderings = $criteria->getOrderings();
        foreach ($orderings as $sort => $order) {
            $hasValidAlias = false;
            foreach ($aliases as $alias) {
                if ($sort !== $alias && 0 === \strpos($sort . '.', $alias . '.')) {
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
     * @param Criteria $criteria
     *
     * @return array [path => join alias, ...]
     */
    private function getJoinMap(Criteria $criteria): array
    {
        $map = [];
        $joins = $criteria->getJoins();
        foreach ($joins as $path => $join) {
            $map[$path] = $join->getAlias();
        }

        return $map;
    }
}
