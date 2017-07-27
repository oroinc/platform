<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\TreeWalkerAdapter;

/**
 * This walker filters a query root entity by current user and organization
 */
class CurrentUserWalker extends TreeWalkerAdapter
{
    const HINT_SECURITY_CONTEXT = 'oro_security.current_user_walker.security_context';

    /**
     * {@inheritdoc}
     */
    public function walkSelectStatement(AST\SelectStatement $AST)
    {
        /** @var Query $query */
        $query = $this->_getQuery();
        if ($query->hasHint(self::HINT_SECURITY_CONTEXT)) {
            $securityContext = $query->getHint(self::HINT_SECURITY_CONTEXT);
            if (!empty($securityContext)) {
                $rootAlias = $this->getRootAlias($AST->fromClause);
                if ($rootAlias) {
                    $conditionalFactors = $this->createCurrentUserConditionalFactors($rootAlias, $securityContext);
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
            }
        }
    }

    /**
     * @param AST\FromClause $from
     *
     * @return string|null
     */
    protected function getRootAlias(AST\FromClause $from)
    {
        foreach ($from->identificationVariableDeclarations as $declaration) {
            if ($declaration->rangeVariableDeclaration->isRoot) {
                return $declaration->rangeVariableDeclaration->aliasIdentificationVariable;
            }
        }

        return null;
    }

    /**
     * @param string $alias
     * @param array  $securityContext
     *
     * @return array
     */
    protected function createCurrentUserConditionalFactors($alias, $securityContext)
    {
        $factors = [];
        foreach ($securityContext as $field => $id) {
            $factors[] = $this->createSimpleConditionalExpression(
                $this->createEqualByIdComparisonExpression($alias, $field, $id)
            );
        }

        return $factors;
    }

    /**
     * @param AST\ConditionalTerm $expr
     *
     * @return AST\ConditionalPrimary
     */
    protected function createConditionalExpression($expr)
    {
        $result = new AST\ConditionalPrimary();

        $result->conditionalExpression = $expr;

        return $result;
    }

    /**
     * @param AST\ComparisonExpression $expr
     *
     * @return AST\ConditionalPrimary
     */
    protected function createSimpleConditionalExpression($expr)
    {
        $result = new AST\ConditionalPrimary();

        $result->simpleConditionalExpression = $expr;

        return $result;
    }

    /**
     * @param string $alias
     * @param string $field
     * @param int    $id
     *
     * @return AST\ComparisonExpression
     */
    protected function createEqualByIdComparisonExpression($alias, $field, $id)
    {
        $pathExpr = new AST\PathExpression(AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $alias, $field);
        $pathExpr->type = AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;

        return new AST\ComparisonExpression(
            $this->createSimpleArithmeticExpression($pathExpr),
            '=',
            $this->createSimpleArithmeticExpression(new AST\Literal(AST\Literal::NUMERIC, (int)$id))
        );
    }

    /**
     * @param AST\Node $expr
     *
     * @return AST\ArithmeticExpression
     */
    protected function createSimpleArithmeticExpression($expr)
    {
        $result = new AST\ArithmeticExpression();

        $result->simpleArithmeticExpression = $expr;

        return $result;
    }
}
