<?php

namespace Oro\Bundle\BatchBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\TreeWalkerAdapter;

/**
 * Modifies AST to use primary keys as main condition
 */
class LimitIdentifierWalker extends TreeWalkerAdapter
{
    const PARAMETER_IDS = 'buffered_result_iterator_keys';

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        $this->_getQuery();
        $queryComponents = $this->_getQueryComponents();
        // Get the root entity and alias from the AST fromClause
        $from = $AST->fromClause->identificationVariableDeclarations;
        if (count($from) !== 1) {
            throw new \LogicException('There is more then 1 From clause');
        }

        $fromRoot = reset($from);
        $rootAlias = $fromRoot->rangeVariableDeclaration->aliasIdentificationVariable;
        $rootClass = $queryComponents[$rootAlias]['metadata'];
        $identifierFieldName = $rootClass->getSingleIdentifierFieldName();

        // create Path Expression
        $pathType = AST\PathExpression::TYPE_STATE_FIELD;
        if (isset($rootClass->associationMappings[$identifierFieldName])) {
            $pathType = AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
        }

        $pathExpression = new AST\PathExpression(
            AST\PathExpression::TYPE_STATE_FIELD | AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
            $rootAlias,
            $identifierFieldName
        );
        $pathExpression->type = $pathType;

        // create Where In Expression
        $arithmeticExpression = new AST\ArithmeticExpression();
        $arithmeticExpression->simpleArithmeticExpression = new AST\SimpleArithmeticExpression([$pathExpression]);
        $expression = new AST\InExpression($arithmeticExpression);
        $expression->literals[] = new AST\InputParameter(':' . self::PARAMETER_IDS);

        // create a condition and insert it to existing Where Expression
        $conditionalPrimary = new AST\ConditionalPrimary;
        $conditionalPrimary->simpleConditionalExpression = $expression;
        if ($AST->whereClause) {
            if ($AST->whereClause->conditionalExpression instanceof AST\ConditionalTerm) {
                $AST->whereClause->conditionalExpression->conditionalFactors[] = $conditionalPrimary;
            } elseif ($AST->whereClause->conditionalExpression instanceof AST\ConditionalPrimary) {
                $AST->whereClause->conditionalExpression = new AST\ConditionalExpression([
                    new AST\ConditionalTerm([
                        $AST->whereClause->conditionalExpression,
                        $conditionalPrimary
                    ])
                ]);
            } elseif ($AST->whereClause->conditionalExpression instanceof AST\ConditionalExpression
                || $AST->whereClause->conditionalExpression instanceof AST\ConditionalFactor
            ) {
                $tmpPrimary = new AST\ConditionalPrimary();
                $tmpPrimary->conditionalExpression = $AST->whereClause->conditionalExpression;
                $AST->whereClause->conditionalExpression = new AST\ConditionalTerm([
                    $tmpPrimary,
                    $conditionalPrimary
                ]);
            }
        } else {
            $AST->whereClause = new AST\WhereClause(
                new AST\ConditionalExpression([
                    new AST\ConditionalTerm([
                        $conditionalPrimary
                    ])
                ])
            );
        }
    }
}
