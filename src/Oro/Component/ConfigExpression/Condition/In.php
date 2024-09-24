<?php

namespace Oro\Component\ConfigExpression\Condition;

class In extends AbstractComparison
{
    #[\Override]
    protected function doCompare($left, $right)
    {
        return in_array($left, $right);
    }

    #[\Override]
    public function getName()
    {
        return 'in';
    }
}
