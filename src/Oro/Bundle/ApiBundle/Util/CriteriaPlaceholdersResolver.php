<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Collection\Criteria;

class CriteriaPlaceholdersResolver
{
    use NormalizeFieldTrait;

    /**
     * @param Criteria $criteria
     * @param string   $rootAlias
     */
    public function resolvePlaceholders(Criteria $criteria, $rootAlias)
    {
        $placeholders = $this->getPlaceholders($criteria, $rootAlias);
        $this->processJoins($criteria, $placeholders);
        $this->processWhere($criteria, $placeholders);
        $this->processOrderBy($criteria, $placeholders);
    }

    /**
     * @param Criteria $criteria
     * @param string   $rootAlias
     *
     * @return array
     */
    protected function getPlaceholders(Criteria $criteria, $rootAlias)
    {
        $placeholders = [Criteria::ROOT_ALIAS_PLACEHOLDER => $rootAlias];
        $joins = $criteria->getJoins();
        foreach ($joins as $path => $join) {
            $placeholders[sprintf(Criteria::PLACEHOLDER_TEMPLATE, $path)] = $join->getAlias();
        }

        return $placeholders;
    }

    /**
     * @param Criteria $criteria
     * @param array    $placeholders
     */
    protected function processJoins(Criteria $criteria, array $placeholders)
    {
        $joins = $criteria->getJoins();
        foreach ($joins as $join) {
            $alias = $join->getAlias();
            $joinPlaceholders = array_merge($placeholders, [Criteria::ENTITY_ALIAS_PLACEHOLDER => $alias]);
            $join->setJoin(strtr($join->getJoin(), $joinPlaceholders));
            $condition = $join->getCondition();
            if ($condition) {
                $join->setCondition(strtr($condition, $joinPlaceholders));
            }
        }
    }

    /**
     * @param Criteria $criteria
     * @param array    $placeholders
     */
    protected function processWhere(Criteria $criteria, array $placeholders)
    {
        $whereExpr = $criteria->getWhereExpression();
        if ($whereExpr) {
            $visitor = new NormalizeExpressionVisitor($placeholders);
            $criteria->where($visitor->dispatch($whereExpr));
        }
    }

    /**
     * @param Criteria $criteria
     * @param array    $placeholders
     */
    protected function processOrderBy(Criteria $criteria, array $placeholders)
    {
        $orderBy = $criteria->getOrderings();
        if (!empty($orderBy)) {
            $normalizedOrderBy = [];
            foreach ($orderBy as $field => $direction) {
                $field = $this->normalizeField($field, $placeholders);
                $normalizedOrderBy[$field] = $direction;
            }
            $criteria->orderBy($normalizedOrderBy);
        }
    }
}
