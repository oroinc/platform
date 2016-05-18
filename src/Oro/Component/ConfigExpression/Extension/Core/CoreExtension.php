<?php

namespace Oro\Component\ConfigExpression\Extension\Core;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\Extension\AbstractExtension;
use Oro\Component\ConfigExpression\Func;

class CoreExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadExpressions()
    {
        return [
            new Condition\Andx(),
            new Condition\Orx(),
            new Condition\Not(),
            new Condition\HasValue(),
            new Condition\NotHasValue(),
            new Condition\Contains(),
            new Condition\NotContains(),
            new Condition\StartWith(),
            new Condition\EndWith(),
            new Condition\In(),
            new Condition\NotIn(),
            new Condition\EqualTo(),
            new Condition\NotEqualTo(),
            new Condition\GreaterThan(),
            new Condition\GreaterThanOrEqual(),
            new Condition\LessThan(),
            new Condition\LessThanOrEqual(),
            new Condition\Blank(),
            new Condition\NotBlank(),
            new Condition\TrueCondition(),
            new Condition\FalseCondition(),
            new Func\GetValue(),
            new Func\Iif(),
            new Func\Join(),
            new Func\Trim()
        ];
    }
}
