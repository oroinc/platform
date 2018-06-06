<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Collection\Criteria;

/**
 * Replaces placeholders in the Criteria object with corresponding object names.
 */
class CriteriaPlaceholdersResolver
{
    use NormalizeFieldTrait;

    /**
     * @param Criteria $criteria
     * @param string   $rootAlias
     */
    public function resolvePlaceholders(Criteria $criteria, string $rootAlias): void
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
    private function getPlaceholders(Criteria $criteria, string $rootAlias): array
    {
        $placeholders = [Criteria::ROOT_ALIAS_PLACEHOLDER => $rootAlias];
        $joins = $criteria->getJoins();
        foreach ($joins as $path => $join) {
            $placeholders[\sprintf(Criteria::PLACEHOLDER_TEMPLATE, $path)] = $join->getAlias();
        }

        return $placeholders;
    }

    /**
     * @param Criteria $criteria
     * @param array    $placeholders
     */
    private function processJoins(Criteria $criteria, array $placeholders): void
    {
        $joins = $criteria->getJoins();
        foreach ($joins as $join) {
            $alias = $join->getAlias();
            $joinPlaceholders = \array_merge($placeholders, [Criteria::ENTITY_ALIAS_PLACEHOLDER => $alias]);
            $join->setJoin(\strtr($join->getJoin(), $joinPlaceholders));
            $condition = $join->getCondition();
            if ($condition) {
                $join->setCondition(\strtr($condition, $joinPlaceholders));
            }
        }
    }

    /**
     * @param Criteria $criteria
     * @param array    $placeholders
     */
    private function processWhere(Criteria $criteria, array $placeholders): void
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
    private function processOrderBy(Criteria $criteria, array $placeholders): void
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
