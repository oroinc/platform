<?php

namespace Oro\Component\ConfigExpression\Condition;

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
