<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression;

use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

interface ExpressionVerifierInterface
{
    /**
     * @param mixed $expression
     *
     * @throws ExpressionException
     *
     * @return mixed
     */
    public function verify($expression);
}
