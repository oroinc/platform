<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks whether a string starts with a specific substring (case-insensitive).
 *
 * This condition evaluates to `true` if the left operand starts with the right operand,
 * using case-insensitive comparison.
 */
class StartWith extends AbstractComparison
{
    #[\Override]
    protected function doCompare($left, $right)
    {
        return stripos($left, $right) === 0;
    }

    #[\Override]
    public function getName()
    {
        return 'start_with';
    }
}
