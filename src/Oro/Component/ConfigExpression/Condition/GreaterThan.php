<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '>' operator.
 */
class GreaterThan extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gt';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->left, $this->right]);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompare($left, $right)
    {
        return $left > $right;
    }
}
