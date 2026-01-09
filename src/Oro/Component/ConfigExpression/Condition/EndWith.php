<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks whether a string ends with a specific substring (case-insensitive).
 *
 * This condition evaluates to `true` if the left operand ends with the right operand,
 * using case-insensitive comparison with regex pattern matching.
 */
class EndWith extends AbstractComparison
{
    #[\Override]
    protected function doCompare($left, $right)
    {
        $pattern = sprintf('/%s$/i', preg_quote($right));

        return (bool) preg_match($pattern, $left);
    }

    #[\Override]
    public function getName()
    {
        return 'end_with';
    }
}
