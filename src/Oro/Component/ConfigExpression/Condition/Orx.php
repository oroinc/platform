<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements logical OR operator.
 */
class Orx extends AbstractComposite
{
    #[\Override]
    public function getName()
    {
        return 'or';
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        foreach ($this->operands as $operand) {
            if ($operand->evaluate($context, $this->errors)) {
                return true;
            }
        }

        return false;
    }
}
