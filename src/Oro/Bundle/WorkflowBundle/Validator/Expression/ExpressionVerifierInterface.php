<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression;

use Oro\Bundle\WorkflowBundle\Validator\Expression\Exception\ExpressionException;

interface ExpressionVerifierInterface
{
    /**
     * @param string $expression
     *
     * @throws ExpressionException
     *
     * @return string
     */
    public function verify($expression);
}
