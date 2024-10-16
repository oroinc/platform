<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '!=' operator.
 */
class NotEqualTo extends EqualTo
{
    #[\Override]
    public function getName()
    {
        return 'neq';
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }
}
