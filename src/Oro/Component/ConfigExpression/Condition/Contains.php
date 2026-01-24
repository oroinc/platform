<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks whether a string contains a substring (case-insensitive).
 *
 * This condition evaluates to `true` if the left operand contains the right operand
 * as a substring, using case-insensitive comparison.
 */
class Contains extends AbstractComparison
{
    #[\Override]
    protected function doCompare($left, $right)
    {
        return stripos($left, $right) !== false;
    }

    #[\Override]
    public function getName()
    {
        return 'contains';
    }
}
