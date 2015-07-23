<?php

namespace Oro\Component\ConfigExpression\Condition;

class NotIn extends In
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
        return 'not_in';
    }
}
