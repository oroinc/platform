<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;

/**
 * Provides functionality to replace computed field names with theirs expressions in WHERE expression.
 */
class ComputedFieldsWhereExpressionModifier extends WhereExpressionModifier
{
    private ?array $computedFieldExpressions = null;

    #[\Override]
    public function updateQuery(QueryBuilder $qb): void
    {
        try {
            parent::updateQuery($qb);
        } finally {
            $this->computedFieldExpressions = null;
        }
    }

    #[\Override]
    protected function walkComparison(Comparison $comparison): mixed
    {
        $field = $comparison->getLeftExpr();
        if (!\is_string($field) || str_contains($field, '.')) {
            return $comparison;
        }
        $fieldExpr = $this->getComputedFieldExpression($field);
        if (null === $fieldExpr) {
            return $comparison;
        }

        return new Comparison($fieldExpr, $comparison->getOperator(), $comparison->getRightExpr());
    }

    private function getComputedFieldExpression(string $field): ?string
    {
        if (null === $this->computedFieldExpressions) {
            $this->computedFieldExpressions = [];
            /** @var Select $select */
            foreach ($this->qb->getDQLPart('select') as $select) {
                foreach ($select->getParts() as $part) {
                    if (preg_match('/(.+) AS ([\w\-]+)$/i', $part, $matches) === 1) {
                        $this->computedFieldExpressions[$matches[2]] = $matches[1];
                    }
                }
            }
        }

        return $this->computedFieldExpressions[$field] ?? null;
    }
}
