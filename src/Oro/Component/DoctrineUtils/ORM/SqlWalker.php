<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\SqlWalker as BaseSqlWalker;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class SqlWalker extends BaseSqlWalker
{
    use HookUnionTrait;

    /**
     * @see https://dev.mysql.com/doc/refman/5.7/en/index-hints.html
     */
    const HINT_USE_INDEX = 'oro.use_index';
    const HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS = 'HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS';

    /**
     * @var bool
     */
    protected $requireOrderByModification = false;

    /**
     * @var array|string[]
     */
    protected $resolvedTableAliases = [];

    /**
     * @var array|array[]
     */
    protected $resolvedColumnAliases = [];

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $this->requireOrderByModification = $AST->orderByClause !== null
            && $this->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform
            && !$this->getQuery()->getHint(self::HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS);

        return parent::walkSelectStatement($AST);
    }

    /**
     * {@inheritdoc}
     */
    public function walkFromClause($fromClause)
    {
        $result = parent::walkFromClause($fromClause);
        $this->saveResolvedTableAliases($fromClause);

        if ($this->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            return $this->walkMysqlFromResult($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function walkSelectClause($selectClause)
    {
        $this->saveResolvedColumnAliases($selectClause);

        return parent::walkSelectClause($selectClause);
    }

    /**
     * @param string $result
     *
     * @return string
     */
    protected function walkMysqlFromResult($result)
    {
        if ($index = $this->getQuery()->getHint(self::HINT_USE_INDEX)) {
            $result = preg_replace('/(\bFROM\s+\w+\s+\w+)/', '\1 USE INDEX (' . $index . ')', $result);
        }

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
    public function walkOrderByItem($orderByItem)
    {
        $item = parent::walkOrderByItem($orderByItem);

        if ($this->requireOrderByModification) {
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
                    $item .= ' NULLS LAST';
                } else {
                    $item .= ' NULLS FIRST';
                }
            }
        }

        return $item;
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

    /**
     * @param AST\FromClause $fromClause
     */
    private function saveResolvedTableAliases(AST\FromClause $fromClause)
    {
        if (!$this->requireOrderByModification) {
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

    /**
     * @param AST\JoinAssociationDeclaration $declaration
     */
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

    /**
     * @param AST\RangeVariableDeclaration $declaration
     */
    private function processRangeDeclaration(AST\RangeVariableDeclaration $declaration)
    {
        $this->resolvedTableAliases[$declaration->aliasIdentificationVariable] = $declaration->abstractSchemaName;
    }

    /**
     * @param AST\SelectClause $selectClause
     */
    private function saveResolvedColumnAliases(AST\SelectClause $selectClause)
    {
        if (!$this->requireOrderByModification) {
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

    /**
     * {@inheritdoc}
     */
    public function walkSubselect($subselect)
    {
        $sql = parent::walkSubselect($subselect);

        return $this->hookUnion($sql);
    }
}
