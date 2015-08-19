<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks whether the value is not empty string and not NULL.
 */
class NotBlank extends Blank
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'not_empty';
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }
}
