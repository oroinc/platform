<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\Query\AST;

/**
 * Align ORDER BY behavior for MySQL and PostgreSQL.
 */
class PostgreSqlOrderByNullsOutputResultModifier extends AbstractOutputResultModifier
{
    public const HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS = 'HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS';

    /**
     * @var array|string[]
     */
    protected $resolvedTableAliases = [];

    /**
     * @var array|array[]
     */
    protected $resolvedColumnAliases = [];

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function isOrderByModificationAllowed(): bool
    {
        return $this->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform
            && !$this->getQuery()->getHint(self::HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS);
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause, string $result)
    {
        $this->saveResolvedTableAliases($fromClause);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause, string $result)
    {
        $this->saveResolvedColumnAliases($selectClause);

        return $result;
    }

    /**
     * Make sort strategy same for MySQL and PostgreSQL.
     *
     * ASC sorting should sort NULL to top, desc in reverse order.
     *
     * @param AST\OrderByItem $orderByItem
     * {@inheritdoc}
     */
    public function walkOrderByItem($orderByItem, string $result)
    {
        if ($this->isOrderByModificationAllowed()) {
            $hasNulls = false;
            $expr = $orderByItem->expression;
            if ($expr instanceof AST\PathExpression) {
                $hasNulls = $this->hasNulls($expr->identificationVariable, $expr->field);
            } elseif (is_string($expr) && array_key_exists($expr, $this->resolvedColumnAliases)) {
                $data = $this->resolvedColumnAliases[$expr];
                $hasNulls = $this->hasNulls($data['table'], $data['field']);
            }

            if ($hasNulls) {
                if ($orderByItem->isDesc()) {
                    $result .= ' NULLS LAST';
                } else {
                    $result .= ' NULLS FIRST';
                }
            }
        }

        return $result;
    }

    /**
     * @param string $tableAlias
     * @param string $field
     * @return bool
     */
    private function hasNulls($tableAlias, $field)
    {
        if (!array_key_exists($tableAlias, $this->resolvedTableAliases)) {
            return false;
        }

        $metadata = $this->getEntityManager()->getClassMetadata($this->resolvedTableAliases[$tableAlias]);
        if ($metadata->hasField($field)) {
            $mapping = $metadata->getFieldMapping($field);

            return !empty($mapping['nullable']);
        } elseif ($metadata->hasAssociation($field)) {
            /** @var array[] $mapping */
            $mapping = $metadata->getAssociationMapping($field);
            foreach ($mapping['joinColumns'] as $joinColumn) {
                if (!empty($joinColumn['nullable'])) {
                    return true;
                }
            }
        }

        return false;
    }

    private function saveResolvedTableAliases(AST\FromClause $fromClause)
    {
        if (!$this->isOrderByModificationAllowed()) {
            return;
        }

        /** @var AST\IdentificationVariableDeclaration $declaration */
        foreach ($fromClause->identificationVariableDeclarations as $declaration) {
            if (null !== $declaration->rangeVariableDeclaration) {
                $this->processRangeDeclaration($declaration->rangeVariableDeclaration);
            }

            /** @var AST\Join $join */
            foreach ($declaration->joins as $join) {
                $joinDeclaration = $join->joinAssociationDeclaration;
                if ($joinDeclaration instanceof AST\JoinAssociationDeclaration) {
                    $this->processJoinAssociationDeclaration($joinDeclaration);
                } elseif ($joinDeclaration instanceof AST\RangeVariableDeclaration) {
                    $this->processRangeDeclaration($joinDeclaration);
                }
            }
        }
    }

    private function processJoinAssociationDeclaration(AST\JoinAssociationDeclaration $declaration)
    {
        $expr = $declaration->joinAssociationPathExpression;
        $metadata = $this->getEntityManager()->getClassMetadata(
            $this->resolvedTableAliases[$expr->identificationVariable]
        );
        if ($metadata->hasAssociation($expr->associationField)) {
            $this->resolvedTableAliases[$declaration->aliasIdentificationVariable] = $metadata
                ->getAssociationTargetClass($expr->associationField);
        }
    }

    private function processRangeDeclaration(AST\RangeVariableDeclaration $declaration)
    {
        $this->resolvedTableAliases[$declaration->aliasIdentificationVariable] = $declaration->abstractSchemaName;
    }

    private function saveResolvedColumnAliases(AST\SelectClause $selectClause)
    {
        if (!$this->isOrderByModificationAllowed()) {
            return;
        }

        /** @var AST\SelectExpression $selectExpression */
        foreach ($selectClause->selectExpressions as $selectExpression) {
            $expr = $selectExpression->expression;
            if ($expr instanceof AST\PathExpression) {
                $identifier = $selectExpression->fieldIdentificationVariable ? : $expr->field;
                $this->resolvedColumnAliases[$identifier] = [
                    'table' => $expr->identificationVariable,
                    'field' => $expr->field
                ];
            }
        }
    }
}
