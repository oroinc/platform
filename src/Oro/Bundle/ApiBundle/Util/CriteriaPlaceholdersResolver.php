<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Criteria as CommonCriteria;
use Oro\Bundle\ApiBundle\Collection\Criteria;

/**
 * Replaces placeholders in the Criteria object with corresponding object names.
 */
class CriteriaPlaceholdersResolver
{
    use NormalizeFieldTrait;

    public function resolvePlaceholders(CommonCriteria $criteria, string $rootAlias): void
    {
        $placeholders = $this->getPlaceholders($criteria, $rootAlias);
        if ($criteria instanceof Criteria) {
            $this->processJoins($criteria, $placeholders);
        }
        $this->processWhere($criteria, $placeholders);
        $this->processOrderBy($criteria, $placeholders);
    }

    private function getPlaceholders(CommonCriteria $criteria, string $rootAlias): array
    {
        $placeholders = [Criteria::ROOT_ALIAS_PLACEHOLDER => $rootAlias];
        if ($criteria instanceof Criteria) {
            $joins = $criteria->getJoins();
            foreach ($joins as $path => $join) {
                $placeholders[sprintf(Criteria::PLACEHOLDER_TEMPLATE, $path)] = $join->getAlias();
            }
        }

        return $placeholders;
    }

    private function processJoins(Criteria $criteria, array $placeholders): void
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

    private function processWhere(CommonCriteria $criteria, array $placeholders): void
    {
        $whereExpr = $criteria->getWhereExpression();
        if ($whereExpr) {
            $visitor = new NormalizeExpressionVisitor($placeholders);
            $criteria->where($visitor->dispatch($whereExpr));
        }
    }

    private function processOrderBy(CommonCriteria $criteria, array $placeholders): void
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
