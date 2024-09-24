<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Checks array or collection has elements.
 */
class HasElements extends NoElements
{
    #[\Override]
    public function getName()
    {
        return 'has_elements';
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        return !parent::isConditionAllowed($context);
    }
}
