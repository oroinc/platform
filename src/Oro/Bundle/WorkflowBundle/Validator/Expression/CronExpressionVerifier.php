<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression;

use Cron\CronExpression;
use Cron\FieldFactory;
use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

class CronExpressionVerifier implements ExpressionVerifierInterface
{
    /**
     * @param mixed $expression
     *
     * @throws ExpressionException
     *
     * @return mixed
     */
    public function verify($expression)
    {
        try {
            CronExpression::factory($expression, new FieldFactory());

            return $expression;
        } catch (\InvalidArgumentException $e) {
            throw new ExpressionException($e->getMessage());
        }
    }
}
