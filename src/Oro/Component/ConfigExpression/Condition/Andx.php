<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements logical AND operator.
 */
class Andx extends AbstractComposite
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'and';
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        foreach ($this->operands as $operand) {
            if (!$operand->evaluate($context, $this->errors)) {
                return false;
            }
        }

        return true;
    }
}
