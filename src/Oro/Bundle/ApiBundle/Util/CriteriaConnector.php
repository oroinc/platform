<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\QueryException;

use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitorFactory;

class CriteriaConnector
{
    /** @var CriteriaNormalizer */
    protected $criteriaNormalizer;

    /** @var CriteriaPlaceholdersResolver */
    protected $placeholdersResolver;

    /** @var QueryExpressionVisitorFactory */
    protected $expressionVisitorFactory;

    /**
     * @param CriteriaNormalizer            $criteriaNormalizer
     * @param CriteriaPlaceholdersResolver  $placeholdersResolver
     * @param QueryExpressionVisitorFactory $expressionVisitorFactory
     */
    public function __construct(
        CriteriaNormalizer $criteriaNormalizer,
        CriteriaPlaceholdersResolver $placeholdersResolver,
        QueryExpressionVisitorFactory $expressionVisitorFactory
    ) {
        $this->criteriaNormalizer = $criteriaNormalizer;
        $this->placeholdersResolver = $placeholdersResolver;
        $this->expressionVisitorFactory = $expressionVisitorFactory;
    }

    /**
     * Adds the given criteria to the query builder.
     *
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     */
    public function applyCriteria(QueryBuilder $qb, Criteria $criteria)
    {
        $this->criteriaNormalizer->normalizeCriteria($criteria);
        $this->placeholdersResolver->resolvePlaceholders($criteria, QueryBuilderUtil::getSingleRootAlias($qb));

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

        $this->addCriteria($qb, $criteria);
    }

    /**
     * Adds criteria to the query.
     * This is a copy of QueryBuilder addCriteria method. We should set another QueryExpressionVisitor that is able
     * to add own comparison or composite expressions.
     *
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     *
     * @throws QueryException
     */
    protected function addCriteria(QueryBuilder $qb, Criteria $criteria)
    {
        $allAliases = $qb->getAllAliases();
        if (!isset($allAliases[0])) {
            throw new QueryException('No aliases are set before invoking addCriteria().');
        }

        $expressionVisitor = $this->expressionVisitorFactory->createExpressionVisitor();
        $expressionVisitor->setQueryAliases($allAliases);

        $whereExpression = $criteria->getWhereExpression();
        if (null !== $whereExpression) {
            $qb->andWhere($expressionVisitor->dispatch($whereExpression));
            $parameters = $expressionVisitor->getParameters();
            foreach ($parameters as $parameter) {
                $qb->getParameters()->add($parameter);
            }
        }

        $orderings = $criteria->getOrderings();
        foreach ($orderings as $sort => $order) {
            $hasValidAlias = false;
            foreach ($allAliases as $alias) {
                if (0 === strpos($sort . '.', $alias . '.')) {
                    $hasValidAlias = true;
                    break;
                }
            }

            if (!$hasValidAlias) {
                $sort = $allAliases[0] . '.' . $sort;
            }

            QueryBuilderUtil::checkField($sort);
            $qb->addOrderBy($sort, QueryBuilderUtil::getSortOrder($order));
        }

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
}
