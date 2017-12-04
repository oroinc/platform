<?php

namespace Oro\Bundle\BatchBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\TreeWalkerAdapter;

class ReduceOrderByWalker extends TreeWalkerAdapter
{
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $queryComponents = $this->_getQueryComponents();
        $from = $AST->fromClause->identificationVariableDeclarations;
        if (count($from) !== 1) {
            throw new \LogicException('There is more then 1 From clause');
        }
        $fromRoot = reset($from);
        $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass = $queryComponents[$rootAlias]['metadata'];
        $identifierFieldName = $rootClass->getSingleIdentifierFieldName();

        if ($AST->orderByClause === null) {
            return;
        }

        foreach ($AST->orderByClause->orderByItems as $i => $orderByClause) {
            if (!$orderByClause->expression instanceof AST\PathExpression
                || !$this->expressionIsIdentifier($orderByClause->expression, $identifierFieldName, $rootAlias)
            ) {
                unset($AST->orderByClause->orderByItems[$i]);
            }
        }
    }

    /**
     * @param AST\PathExpression $expression
     * @param string $identifierFieldName
     * @param string $rootAlias
     * @return bool
     */
    private function expressionIsIdentifier(
        AST\PathExpression $expression,
        string $identifierFieldName,
        string $rootAlias
    ) {
        return $expression->field === $identifierFieldName
            && $expression->identificationVariable === $rootAlias;
    }
}
