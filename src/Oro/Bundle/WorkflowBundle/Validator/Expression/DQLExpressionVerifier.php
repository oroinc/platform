<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\QueryException;

use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

class DQLExpressionVerifier implements ExpressionVerifierInterface
{
    /**
     * {@inheritdoc}
     */
    public function verify($expression)
    {
        if (!$expression instanceof AbstractQuery) {
            throw new \InvalidArgumentException(
                sprintf(
                    '$expression must be instance of Doctrine\ORM\AbstractQuery. "%s" given',
                    gettype($expression)
                )
            );
        }

        try {
            //Try to execute only "SELECT" queries, because they are safe
            if ($expression->getAST() instanceof SelectStatement) {
                $expression->setFirstResult(0)->setMaxResults(1)->execute();
            }

            return true;
        } catch (QueryException $e) {
            throw new ExpressionException($e->getMessage());
        }
    }
}
