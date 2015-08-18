<?php

namespace Oro\Component\ConfigExpression\Condition;

class EndWith extends AbstractComparison
{
    /**
     * {@inheritdoc}
     */
    protected function doCompare($left, $right)
    {
        $pattern = sprintf('/%s$/i', preg_quote($right));

        return (bool) preg_match($pattern, $left);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'end_with';
    }
}
