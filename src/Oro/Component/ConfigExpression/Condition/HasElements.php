<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\Exception;

/**
 * Checks array or collection has elements.
 */
class HasElements extends NoElements
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'has_elements';
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }
}
