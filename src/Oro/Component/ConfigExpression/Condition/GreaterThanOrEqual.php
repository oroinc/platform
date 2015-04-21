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
    protected function doCompare($left, $right)
    {
        return $left >= $right;
    }
}
