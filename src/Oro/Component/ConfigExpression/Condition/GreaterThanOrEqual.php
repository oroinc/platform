<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '>=' operator.
 */
class GreaterThanOrEqual extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gte';
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
        return $left >= $right;
    }
}
