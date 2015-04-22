<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '<=' operator.
 */
class LessThanOrEqual extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'lte';
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompare($left, $right)
    {
        return $left <= $right;
    }
}
