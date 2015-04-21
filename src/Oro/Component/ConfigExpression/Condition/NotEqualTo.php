<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '!=' operator.
 */
class NotEqualTo extends EqualTo
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'neq';
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }
}
