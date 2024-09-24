<?php

namespace Oro\Component\ConfigExpression\Condition;

class EndWith extends AbstractComparison
{
    #[\Override]
    protected function doCompare($left, $right)
    {
        $pattern = sprintf('/%s$/i', preg_quote($right));

        return (bool) preg_match($pattern, $left);
    }

    #[\Override]
    public function getName()
    {
        return 'end_with';
    }
}
