<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\TreeWalkerAdapter;

/**
 * This walker can be used to modify ORDER BY clause to ensure that records
 * will be returned in the same order independent from a state of SQL server
 * and from values of OFFSET and LIMIT clauses.
 * To achieve this the PK of the root entity is added to the end of ORDER BY clause.
 * @link https://www.postgresql.org/docs/8.1/static/queries-limit.html
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PreciseOrderByWalker extends TreeWalkerAdapter
{
    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $rootEntityClass = null;
        $rootEntityAlias = null;
        /** @var AST\IdentificationVariableDeclaration $declaration */
        foreach ($AST->fromClause->identificationVariableDeclarations as $declaration) {
            if ($declaration->rangeVariableDeclaration->isRoot) {
                $rootEntityClass = $declaration->rangeVariableDeclaration->abstractSchemaName;
                $rootEntityAlias = $declaration->rangeVariableDeclaration->aliasIdentificationVariable;
                break;
            }
        }
        if ($rootEntityClass && $rootEntityAlias) {
            $this->updateQuery($AST, $rootEntityClass, $rootEntityAlias);
        }
    }

    /**
     * @param AST\SelectStatement $AST
     * @param                     $rootEntityClass
     * @param                     $rootEntityAlias
     */
    private function updateQuery(AST\SelectStatement $AST, $rootEntityClass, $rootEntityAlias)
    {
        $orderByClause = $AST->orderByClause;
        if (null === $orderByClause) {
            $orderByClause = new AST\OrderByClause([]);
        }
        /** @var AST\OrderByItem[] $itemsToAdd */
        $itemsToAdd = [];
        $fieldNamesToCheck = $this->getIdentifierFieldNames($rootEntityClass);
        if (null !== $AST->groupByClause && !empty($AST->groupByClause->groupByItems)) {
            $fieldNamesToCheck = $this->processGroupBy(
                $AST->groupByClause,
                $rootEntityAlias,
                $fieldNamesToCheck,
                $AST->selectClause
            );
        }
        $direction = $this->getOrderByDirection($orderByClause);
        foreach ($fieldNamesToCheck as $fieldName) {
            if (!$this->hasOrderByField($orderByClause, $rootEntityAlias, $fieldName, $AST->selectClause)) {
                $itemsToAdd[] = $this->createOrderByItem($rootEntityAlias, $fieldName, $direction);
            }
        }
        if (!empty($itemsToAdd)) {
            foreach ($itemsToAdd as $item) {
                $orderByClause->orderByItems[] = $item;
                /** @var AST\PathExpression $expr */
                $expr = $item->expression;
                if ($AST->selectClause->isDistinct) {
                    $this->ensureFieldExistsInSelect(
                        $AST->selectClause,
                        $expr->identificationVariable,
                        $expr->field
                    );
                }
            }
            if (null === $AST->orderByClause && !empty($orderByClause->orderByItems)) {
                $AST->orderByClause = $orderByClause;
            }
        }
    }

    /**
     * @param AST\GroupByClause $groupByClause
     * @param string            $rootEntityAlias
     * @param string[]          $fieldNamesToCheck
     * @param AST\SelectClause  $selectClause
     *
     * @return string[]
     */
    private function processGroupBy(
        AST\GroupByClause $groupByClause,
        $rootEntityAlias,
        array $fieldNamesToCheck,
        AST\SelectClause $selectClause
    ) {
        $fieldNamesToAdd = [];
        foreach ($groupByClause->groupByItems as $groupByItem) {
            foreach ($fieldNamesToCheck as $fieldName) {
                if ($this->isExpressionByField($groupByItem, $rootEntityAlias, $fieldName, $selectClause)) {
                    $fieldNamesToAdd[] = $fieldName;
                }
            }
        }

        return $fieldNamesToAdd;
    }

    /**
     * @param AST\SelectClause $selectClause
     * @param string           $entityAlias
     * @param string           $fieldName
     */
    private function ensureFieldExistsInSelect(AST\SelectClause $selectClause, $entityAlias, $fieldName)
    {
        if (!$this->hasSelectField($selectClause, $entityAlias, $fieldName)) {
            $selectClause->selectExpressions[] = $this->createHiddenSelectExpression(
                $entityAlias,
                $fieldName
            );
        }
    }

    /**
     * @param string $entityClass
     *
     * @return string[]
     */
    private function getIdentifierFieldNames($entityClass)
    {
        return $this->_getQuery()
            ->getEntityManager()
            ->getClassMetadata($entityClass)
            ->getIdentifierFieldNames();
    }

    /**
     * @param mixed            $expr
     * @param string           $entityAlias
     * @param string           $fieldName
     * @param AST\SelectClause $selectClause
     *
     * @return bool
     */
    private function isExpressionByField($expr, $entityAlias, $fieldName, AST\SelectClause $selectClause)
    {
        if ($expr instanceof AST\PathExpression) {
            return $this->isPathExpressionByField($expr, $entityAlias, $fieldName);
        } elseif (is_string($expr)) {
            /** @var AST\SelectExpression $selectExpr */
            foreach ($selectClause->selectExpressions as $selectExpr) {
                if ($expr === $selectExpr->fieldIdentificationVariable) {
                    return $selectExpr->expression instanceof AST\PathExpression
                        ? $this->isPathExpressionByField($selectExpr->expression, $entityAlias, $fieldName)
                        : false;
                }
            }
        }

        return false;
    }

    /**
     * @param AST\PathExpression $expr
     * @param string             $entityAlias
     * @param string             $fieldName
     *
     * @return bool
     */
    private function isPathExpressionByField(AST\PathExpression $expr, $entityAlias, $fieldName)
    {
        return
            AST\PathExpression::TYPE_STATE_FIELD === $expr->type
            && $entityAlias === $expr->identificationVariable
            && $fieldName === $expr->field;
    }

    /**
     * @param AST\SelectClause $selectClause
     * @param string           $entityAlias
     * @param string           $fieldName
     *
     * @return bool
     */
    private function hasSelectField(
        AST\SelectClause $selectClause,
        $entityAlias,
        $fieldName
    ) {
        $result = false;
        /** @var AST\SelectExpression $selectExpr */
        foreach ($selectClause->selectExpressions as $selectExpr) {
            if ($selectExpr->expression instanceof AST\PathExpression) {
                if ($this->isPathExpressionByField($selectExpr->expression, $entityAlias, $fieldName)) {
                    $result = true;
                    break;
                }
            } elseif (null === $selectExpr->fieldIdentificationVariable) {
                if ($selectExpr->expression instanceof AST\PartialObjectExpression) {
                    if ($entityAlias === $selectExpr->expression->identificationVariable
                        && in_array($fieldName, $selectExpr->expression->partialFieldSet, true)
                    ) {
                        $result = true;
                        break;
                    }
                } elseif (is_string($selectExpr->expression) && $entityAlias = $selectExpr->expression) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param AST\OrderByClause $orderByClause
     * @param string            $entityAlias
     * @param string            $fieldName
     * @param AST\SelectClause  $selectClause
     *
     * @return bool
     */
    private function hasOrderByField(
        AST\OrderByClause $orderByClause,
        $entityAlias,
        $fieldName,
        AST\SelectClause $selectClause
    ) {
        $result = false;
        /** @var AST\OrderByItem $orderByItem */
        foreach ($orderByClause->orderByItems as $orderByItem) {
            if ($this->isExpressionByField($orderByItem->expression, $entityAlias, $fieldName, $selectClause)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * @param AST\OrderByClause $orderByClause
     *
     * @return string
     */
    private function getOrderByDirection(AST\OrderByClause $orderByClause)
    {
        $direction = 'ASC';
        if (!empty($orderByClause->orderByItems)) {
            /** @var AST\OrderByItem $lastOrderByItem */
            $lastOrderByItem = end($orderByClause->orderByItems);
            if ($lastOrderByItem->isDesc()) {
                $direction = 'DESC';
            }
        }

        return $direction;
    }

    /**
     * @param string $entityAlias
     * @param string $fieldName
     *
     * @return AST\SelectExpression
     */
    private function createHiddenSelectExpression($entityAlias, $fieldName)
    {
        return new AST\SelectExpression(
            $this->createFieldPathExpression($entityAlias, $fieldName),
            null,
            true
        );
    }

    /**
     * @param string $entityAlias
     * @param string $fieldName
     * @param string $direction
     *
     * @return AST\OrderByItem
     */
    private function createOrderByItem($entityAlias, $fieldName, $direction = 'ASC')
    {
        $orderByItem = new AST\OrderByItem(
            $this->createFieldPathExpression($entityAlias, $fieldName)
        );
        $orderByItem->type = $direction;

        return $orderByItem;
    }

    /**
     * @param string $entityAlias
     * @param string $fieldName
     *
     * @return AST\PathExpression
     */
    private function createFieldPathExpression($entityAlias, $fieldName)
    {
        $expr = new AST\PathExpression(AST\PathExpression::TYPE_STATE_FIELD, $entityAlias, $fieldName);
        $expr->type = AST\PathExpression::TYPE_STATE_FIELD;

        return $expr;
    }
}
