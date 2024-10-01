<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks whether the value does not exist in the context.
 */
class NotHasValue extends HasValue
{
    #[\Override]
    public function getName()
    {
        return 'not_has';
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }
}
