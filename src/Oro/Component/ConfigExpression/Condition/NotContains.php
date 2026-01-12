<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks whether a string does not contain a substring (case-insensitive).
 *
 * This condition is the logical negation of the {@see Contains} condition, evaluating to `true`
 * if the left operand does not contain the right operand as a substring.
 */
class NotContains extends Contains
{
    #[\Override]
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }

    #[\Override]
    public function getName()
    {
        return 'not_contains';
    }
}
