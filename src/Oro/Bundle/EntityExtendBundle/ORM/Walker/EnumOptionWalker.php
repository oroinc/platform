<?php

namespace Oro\Bundle\EntityExtendBundle\ORM\Walker;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\TreeWalkerAdapter;

/**
 * This walker filters a query root enum options entity by specified enum code.
 */
class EnumOptionWalker extends TreeWalkerAdapter
{
    private const HINT = 'oro_entity_extend.enum_option';

    #[\Override]
    public function walkSelectStatement(AST\SelectStatement $AST): void
    {
        /** @var Query $query */
        $query = $this->_getQuery();
        $rootAlias = $this->getRootAlias($AST->fromClause);
        if (!$rootAlias) {
            return;
        }

        $conditionalFactors = $this->createEnumCodeConditionalFactors($rootAlias, $query->getHint(self::HINT));
        if (null === $AST->whereClause) {
            $AST->whereClause = new AST\WhereClause(new AST\ConditionalTerm($conditionalFactors));
        } elseif ($AST->whereClause->conditionalExpression instanceof AST\ConditionalPrimary) {
            // 'where' part has only one condition
            array_unshift($conditionalFactors, $AST->whereClause->conditionalExpression);
            $AST->whereClause->conditionalExpression = new AST\ConditionalTerm($conditionalFactors);
        } else {
            // 'where' part has more than one condition
            if (isset($AST->whereClause->conditionalExpression->conditionalFactors)) {
                $AST->whereClause->conditionalExpression->conditionalFactors = array_merge(
                    $AST->whereClause->conditionalExpression->conditionalFactors,
                    $conditionalFactors
                );
            } else {
                array_unshift(
                    $conditionalFactors,
                    $this->createConditionalExpression($AST->whereClause->conditionalExpression)
                );
                $AST->whereClause->conditionalExpression = new AST\ConditionalTerm($conditionalFactors);
            }
        }
    }

    private function getRootAlias(AST\FromClause $from): ?string
    {
        foreach ($from->identificationVariableDeclarations as $declaration) {
            if ($declaration->rangeVariableDeclaration->isRoot) {
                return $declaration->rangeVariableDeclaration->aliasIdentificationVariable;
            }
        }

        return null;
    }

    private function createEnumCodeConditionalFactors(string $alias, string $enumCode): array
    {
        return [
            $this->createSimpleConditionalExpression(
                new AST\ComparisonExpression(
                    $this->createSimpleArithmeticExpression($this->createEnumCodePathExpression($alias)),
                    '=',
                    $this->createSimpleArithmeticExpression(new AST\Literal(AST\Literal::STRING, $enumCode))
                )
            )
        ];
    }

    private function createEnumCodePathExpression(string $alias): AST\PathExpression
    {
        $result = new AST\PathExpression(AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $alias, 'enumCode');
        $result->type = AST\PathExpression::TYPE_STATE_FIELD;

        return $result;
    }

    private function createConditionalExpression(AST\ConditionalExpression $expr): AST\ConditionalPrimary
    {
        $result = new AST\ConditionalPrimary();
        $result->conditionalExpression = $expr;

        return $result;
    }

    private function createSimpleConditionalExpression(AST\ComparisonExpression $expr): AST\ConditionalPrimary
    {
        $result = new AST\ConditionalPrimary();
        $result->simpleConditionalExpression = $expr;

        return $result;
    }

    private function createSimpleArithmeticExpression(AST\Node $expr): AST\ArithmeticExpression
    {
        $result = new AST\ArithmeticExpression();
        $result->simpleArithmeticExpression = $expr;

        return $result;
    }
}
