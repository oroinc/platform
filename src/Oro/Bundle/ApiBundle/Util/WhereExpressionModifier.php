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
            $qb->where($this->dispatch($whereExpression));
        } finally {
            $this->qb = null;
        }
    }

    abstract protected function walkComparison(Comparison $comparison): mixed;

    protected function dispatch(mixed $expr): mixed
    {
        switch (true) {
            case $expr instanceof Andx:
                return new Andx($this->walkCompositeParts($expr->getParts()));
            case $expr instanceof Orx:
                return new Orx($this->walkCompositeParts($expr->getParts()));
            case $expr instanceof Comparison:
                return $this->walkComparison($expr);
            default:
                return $expr;
        }
    }

    private function walkCompositeParts(array $parts): array
    {
        $result = [];
        foreach ($parts as $part) {
            $result[] = $this->dispatch($part);
        }

        return $result;
    }
}
