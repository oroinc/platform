<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks whether a value exists in an array.
 *
 * This condition evaluates to `true` if the left operand is found in the right operand array,
 * using standard PHP array membership testing.
 */
class In extends AbstractComparison
{
    #[\Override]
    protected function doCompare($left, $right)
    {
        return in_array($left, $right);
    }

    #[\Override]
    public function getName()
    {
        return 'in';
    }
}
