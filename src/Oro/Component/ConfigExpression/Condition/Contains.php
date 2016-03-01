<?php

namespace Oro\Component\ConfigExpression\Condition;

class Contains extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    protected function doCompare($left, $right)
    {
        return stripos($left, $right) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'contains';
    }
}
