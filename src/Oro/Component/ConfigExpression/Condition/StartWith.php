<?php

namespace Oro\Component\ConfigExpression\Condition;

class StartWith extends AbstractComparison
{
    #[\Override]
    protected function doCompare($left, $right)
    {
        return stripos($left, $right) === 0;
    }

    #[\Override]
    public function getName()
    {
        return 'start_with';
    }
}
