<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements logical OR operator.
 */
class Orx extends AbstractComposite
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'or';
    }

    /**
     * {@inheritdoc}
     */
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
