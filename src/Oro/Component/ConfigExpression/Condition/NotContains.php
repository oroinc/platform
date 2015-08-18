<?php

namespace Oro\Component\ConfigExpression\Condition;

class NotContains extends Contains
{
    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'not_contains';
    }
}
