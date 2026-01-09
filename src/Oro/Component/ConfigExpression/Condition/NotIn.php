<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks whether a value does not exist in an array.
 *
 * This condition is the logical negation of the {@see In} condition, evaluating to `true`
 * if the left operand is not found in the right operand array.
 */
class NotIn extends In
{
    #[\Override]
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }

    #[\Override]
    public function getName()
    {
        return 'not_in';
    }
}
