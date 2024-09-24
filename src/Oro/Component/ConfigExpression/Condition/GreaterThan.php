<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '>' operator.
 */
class GreaterThan extends AbstractComparison
{
    #[\Override]
    public function getName()
    {
        return 'gt';
    }

    #[\Override]
    protected function doCompare($left, $right)
    {
        return $left > $right;
    }
}
