<?php

namespace Oro\Component\Action\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition as BaseAbstractCondition;

abstract class AbstractCondition extends BaseAbstractCondition
{
    #[\Override]
    public function toArray()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
