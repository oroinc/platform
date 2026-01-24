<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression;

use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

/**
 * Defines the contract for verifying expression validity.
 *
 * Implementations validate expressions (such as cron expressions) and throw exceptions
 * if the expression is invalid or cannot be parsed.
 */
interface ExpressionVerifierInterface
{
    /**
     * @param mixed $expression
     *
     * @throws ExpressionException
     *
     * @return bool
     */
    public function verify($expression);
}
