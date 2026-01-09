<?php

namespace Oro\Component\Action\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition as BaseAbstractCondition;

/**
 * Provides common functionality for action conditions.
 *
 * This base class extends the config expression condition to provide action-specific condition implementations.
 * It disables compilation and array conversion methods that are not needed for action conditions.
 * Subclasses should implement the `isConditionAllowed` method.
 */
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
