<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '==' operator.
 */
class EqualTo extends AbstractComparison
{
    #[\Override]
    public function getName()
    {
        return 'eq';
    }

    #[\Override]
    protected function doCompare($left, $right)
    {
        return $left == $right;
    }
}
