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
    protected function doCompare($left, $right)
    {
        return $left > $right;
    }
}
