<?php

namespace Oro\Bundle\TranslationBundle\Api\Repository;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\ApiBundle\Util\WhereExpressionModifier;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides functionality to optimize using joined language entity in translation query.
 */
class TranslationLanguageJoinOptimizer extends WhereExpressionModifier
{
    private ?string $languageCode = null;

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    /**
     * {@inheritDoc}
     */
    protected function processWhereExpression(mixed $expr): mixed
    {
        if (!$this->hasJoin('language')) {
            return $expr;
        }

        $comparisons = [];
        $this->findComparisons($expr, $comparisons);
        if (!$comparisons) {
            return $expr;
        }

        $languageCode = $this->findLanguageCode($comparisons);
        if (null === $languageCode) {
            return $expr;
        }

        if (!$this->hasJoin('translation')) {
            $this->removeJoin('language');
        }
        $expr = parent::processWhereExpression($expr);
        $this->removeOrderByLanguageCode();

        $this->languageCode = $languageCode;

        return $expr;
    }

    /**
     * {@inheritDoc}
     */
    protected function walkComparison(Comparison $comparison): mixed
    {
        if (!$this->isLanguageCodeFilter($comparison)) {
            return $comparison;
        }

        $languageJoin = QueryBuilderUtil::findJoinByAlias($this->qb, 'language');
        if (null === $languageJoin) {
            $this->removeQueryParameter(substr($comparison->getRightExpr(), 1));
        } else {
            $this->replaceJoin('language', new Join(
                $languageJoin->getJoinType(),
                $languageJoin->getJoin(),
                $languageJoin->getAlias(),
                $languageJoin->getConditionType(),
                'language.code = ' . $comparison->getRightExpr(),
                $languageJoin->getIndexBy()
            ));
        }

        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isLanguageCodeFilter(Comparison $comparison): bool
    {
        if ('language.code' !== $comparison->getLeftExpr() || Comparison::EQ !== $comparison->getOperator()) {
            return false;
        }

        $languageCodeParamNameExpr = $comparison->getRightExpr();
        if (!\is_string($languageCodeParamNameExpr)
            || !$languageCodeParamNameExpr
            || !str_starts_with($languageCodeParamNameExpr, ':')
        ) {
            return false;
        }

        $languageCodeParam = $this->qb->getParameter(substr($languageCodeParamNameExpr, 1));
        if (null === $languageCodeParam) {
            return false;
        }

        $languageCode = $languageCodeParam->getValue();
        if (!\is_string($languageCode) || !$languageCode || preg_match('/[\W]+/', $languageCode)) {
            return false;
        }

        return true;
    }

    private function findComparisons(mixed $expr, ?array &$comparisons): void
    {
        if (null === $comparisons) {
            return;
        }

        switch (true) {
            case $expr instanceof Andx:
                foreach ($expr->getParts() as $part) {
                    $this->findComparisons($part, $comparisons);
                }
                break;
            case $expr instanceof Orx:
                $comparisons = null;
                break;
            case $expr instanceof Comparison:
                $comparisons[] = $expr;
                break;
        }
    }

    private function findLanguageCode(array $comparisons): ?string
    {
        $comparisonByLanguageCode = null;
        /** @var Comparison $comparison */
        foreach ($comparisons as $comparison) {
            if ($this->isLanguageCodeFilter($comparison)) {
                $comparisonByLanguageCode = $comparison;
                break;
            }
        }
        if (null === $comparisonByLanguageCode) {
            return null;
        }

        return $this->qb->getParameter(substr($comparisonByLanguageCode->getRightExpr(), 1))->getValue();
    }

    private function removeOrderByLanguageCode(): void
    {
        /** @var OrderBy[] $orderByPart */
        $orderByPart = $this->qb->getDQLPart('orderBy');
        if (!$orderByPart) {
            return;
        }

        $this->qb->resetDQLPart('orderBy');
        foreach ($orderByPart as $orderBy) {
            if (!$this->hasSortByField($orderBy, 'language.code')) {
                $this->qb->addOrderBy($orderBy);
            }
        }
    }

    private function hasSortByField(OrderBy $orderBy, string $field): bool
    {
        foreach ($orderBy->getParts() as $part) {
            if (str_starts_with($part, $field . ' ')) {
                return true;
            }
        }

        return false;
    }

    private function removeQueryParameter(string $name): void
    {
        /** @var Parameter[] $parameters */
        $parameters = $this->qb->getParameters()->toArray();
        $this->qb->getParameters()->clear();
        foreach ($parameters as $parameter) {
            if ($parameter->getName() !== $name) {
                $this->qb->getParameters()->add($parameter);
            }
        }
    }

    private function hasJoin(string $alias): bool
    {
        return null !== QueryBuilderUtil::findJoinByAlias($this->qb, $alias);
    }

    private function removeJoin(string $alias): void
    {
        $joinPart = $this->qb->getDQLPart('join');
        $this->qb->resetDQLPart('join');
        foreach ($joinPart as $joins) {
            /** @var Join $join */
            foreach ($joins as $join) {
                if ($join->getAlias() !== $alias) {
                    QueryBuilderUtil::addJoin($this->qb, $join);
                }
            }
        }
    }

    private function replaceJoin(string $alias, Join $newJoin): void
    {
        $joinParts = $this->qb->getDQLPart('join');
        if (!$joinParts) {
            return;
        }

        $this->qb->resetDQLPart('join');
        foreach ($joinParts as $joins) {
            foreach ($joins as $join) {
                if ($join->getAlias() === $alias) {
                    QueryBuilderUtil::addJoin($this->qb, $newJoin);
                } else {
                    QueryBuilderUtil::addJoin($this->qb, $join);
                }
            }
        }
    }
}
