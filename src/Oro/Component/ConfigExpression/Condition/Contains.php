<?php

namespace Oro\Component\ConfigExpression\Condition;

class Contains extends AbstractComparison
{
    #[\Override]
    protected function doCompare($left, $right)
    {
        return stripos($left, $right) !== false;
    }

    #[\Override]
    public function getName()
    {
        return 'contains';
    }
}
