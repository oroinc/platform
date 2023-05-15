<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;

/**
 * The base class for a query builder WHERE expression modifiers.
 */
abstract class WhereExpressionModifier
{
    protected ?QueryBuilder $qb;

    public function updateQuery(QueryBuilder $qb): void
    {
        $whereExpression = $qb->getDQLPart('where');
        if (!$whereExpression) {
            return;
        }

        $this->qb = $qb;
        try {
            $whereExpression = $this->processWhereExpression($whereExpression);
        } finally {
            $this->qb = null;
        }

        if (null === $whereExpression) {
            $qb->resetDQLPart('where');
        } else {
            $qb->where($whereExpression);
        }
    }

    abstract protected function walkComparison(Comparison $comparison): mixed;

    protected function dispatch(mixed $expr): mixed
    {
        switch (true) {
            case $expr instanceof Andx:
                $parts = $this->walkCompositeParts($expr->getParts());

                return $parts ? new Andx($parts) : null;
            case $expr instanceof Orx:
                $parts = $this->walkCompositeParts($expr->getParts());

                return $parts ? new Orx($parts) : null;
            case $expr instanceof Comparison:
                return $this->walkComparison($expr);
            default:
                return $expr;
        }
    }

    protected function processWhereExpression(mixed $expr): mixed
    {
        return $this->dispatch($expr);
    }

    private function walkCompositeParts(array $parts): array
    {
        $result = [];
        foreach ($parts as $part) {
            $expr = $this->dispatch($part);
            if (null !== $expr) {
                $result[] = $expr;
            }
        }

        return $result;
    }
}
