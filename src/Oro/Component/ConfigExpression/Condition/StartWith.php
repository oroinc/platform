<?php

namespace Oro\Component\ConfigExpression\Condition;

class StartWith extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    protected function doCompare($left, $right)
    {
        return stripos($left, $right) === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'start_with';
    }
}
