<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\QueryException;

use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitorFactory;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class CriteriaConnector
{
    /** @var CriteriaNormalizer */
    protected $criteriaNormalizer;

    /** @var CriteriaPlaceholdersResolver */
    protected $placeholdersResolver;

    /** @var QueryExpressionVisitorFactory */
    protected $expressionVisitorFactory;

    /** @var EntityClassResolver */
    private $entityClassResolver;

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
     * @param EntityClassResolver $entityClassResolver
     * @deprecated will be removed in 3.0
     */
    public function setEntityClassResolver(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * Adds the given criteria to the query builder.
     *
     * @param QueryBuilder $qb
     * @param Criteria     $criteria
     */
    public function applyCriteria(QueryBuilder $qb, Criteria $criteria)
    {
        $rootAlias = QueryBuilderUtil::getSingleRootAlias($qb);
        $rootEntityClass = $this->entityClassResolver->getEntityClass($qb->getRootEntities()[0]);
        $this->criteriaNormalizer->setRootEntityClass($rootEntityClass);
        $this->criteriaNormalizer->normalizeCriteria($criteria);
        $this->placeholdersResolver->resolvePlaceholders($criteria, $rootAlias);

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
        $aliases = $qb->getAllAliases();
        if (!isset($aliases[0])) {
            throw new QueryException('No aliases are set before invoking addCriteria().');
        }

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
    private function processWhere(QueryBuilder $qb, Criteria $criteria, array $aliases)
    {
        $expressionVisitor = $this->expressionVisitorFactory->createExpressionVisitor();
        $expressionVisitor->setQueryAliases($aliases);

        $whereExpression = $criteria->getWhereExpression();
        if (null !== $whereExpression) {
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
    private function processOrderings(QueryBuilder $qb, Criteria $criteria, array $aliases)
    {
        $orderings = $criteria->getOrderings();
        foreach ($orderings as $sort => $order) {
            $hasValidAlias = false;
            foreach ($aliases as $alias) {
                if ($sort !== $alias && 0 === strpos($sort . '.', $alias . '.')) {
                    $hasValidAlias = true;
                    break;
                }
            }

            if (!$hasValidAlias) {
                $sort = $aliases[0] . '.' . $sort;
            }

            $qb->addOrderBy($sort, $order);
        }
    }
}
