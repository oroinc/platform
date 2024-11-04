<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '>=' operator.
 */
class GreaterThanOrEqual extends AbstractComparison
{
    #[\Override]
    public function getName()
    {
        return 'gte';
    }

    #[\Override]
    protected function doCompare($left, $right)
    {
        return $left >= $right;
    }
}
