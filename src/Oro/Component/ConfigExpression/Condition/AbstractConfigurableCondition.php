<?php

namespace Oro\Component\ConfigExpression\Condition;

abstract class AbstractConfigurableCondition extends AbstractCondition
{
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
