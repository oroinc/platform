<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '<' operator.
 */
class LessThan extends AbstractComparison
{
    #[\Override]
    public function getName()
    {
        return 'lt';
    }

    #[\Override]
    protected function doCompare($left, $right)
    {
        return $left < $right;
    }
}
