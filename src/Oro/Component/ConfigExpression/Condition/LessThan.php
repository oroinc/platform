<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '<' operator.
 */
class LessThan extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'lt';
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompare($left, $right)
    {
        return $left < $right;
    }
}
