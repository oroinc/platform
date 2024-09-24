<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '<=' operator.
 */
class LessThanOrEqual extends AbstractComparison
{
    #[\Override]
    public function getName()
    {
        return 'lte';
    }

    #[\Override]
    protected function doCompare($left, $right)
    {
        return $left <= $right;
    }
}
