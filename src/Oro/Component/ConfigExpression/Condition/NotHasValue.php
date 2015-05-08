<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks whether the value does not exist in the context.
 */
class NotHasValue extends HasValue
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'not_has';
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }
}
