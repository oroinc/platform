<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression;

use Cron\CronExpression;
use Cron\FieldFactory;
use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

/**
 * Verifies the validity of cron expressions.
 *
 * This verifier validates cron expression syntax using the cron expression library,
 * throwing an exception if the expression is invalid.
 */
class CronExpressionVerifier implements ExpressionVerifierInterface
{
    #[\Override]
    public function verify($expression)
    {
        try {
            CronExpression::factory($expression, new FieldFactory());

            return true;
        } catch (\InvalidArgumentException $e) {
            throw new ExpressionException($e->getMessage());
        }
    }
}
