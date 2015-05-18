<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition as BaseAbstractCondition;

abstract class AbstractCondition extends BaseAbstractCondition
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
