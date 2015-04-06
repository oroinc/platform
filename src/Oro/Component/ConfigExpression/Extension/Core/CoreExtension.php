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
            new Condition\EqualTo(),
            new Condition\NotEqualTo(),
            new Condition\GreaterThan(),
            new Condition\GreaterThanOrEqual(),
            new Condition\LessThan(),
            new Condition\LessThanOrEqual(),
            new Condition\Blank(),
            new Condition\NotBlank(),
            new Condition\True(),
            new Condition\False(),
            new Func\GetValue(),
            new Func\Iif(),
            new Func\Join(),
            new Func\Trim()
        ];
    }
}
