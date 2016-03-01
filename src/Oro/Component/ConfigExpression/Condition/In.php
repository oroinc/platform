<?php

namespace Oro\Component\ConfigExpression\Condition;

class In extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    protected function doCompare($left, $right)
    {
        return in_array($left, $right);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'in';
    }
}
