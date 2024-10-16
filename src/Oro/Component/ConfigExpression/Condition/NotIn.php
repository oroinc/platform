<?php

namespace Oro\Component\ConfigExpression\Condition;

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
