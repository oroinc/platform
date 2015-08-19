<?php

namespace Oro\Component\ConfigExpression\Condition;

/**
 * Implements '==' operator.
 */
class EqualTo extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'eq';
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompare($left, $right)
    {
        return $left == $right;
    }
}
