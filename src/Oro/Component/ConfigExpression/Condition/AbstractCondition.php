<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\AbstractExpression;

/**
 * Provides common functionality for configuration expression conditions.
 *
 * This base class implements the evaluation flow for conditions, checking if the condition is allowed
 * and adding errors when it fails.
 * Subclasses must implement the `isConditionAllowed` method to define the specific condition logic.
 */
abstract class AbstractCondition extends AbstractExpression
{
    /**
     *
     * @return boolean
     */
    #[\Override]
    protected function doEvaluate($context)
    {
        $result = $this->isConditionAllowed($context);
        if (!$result) {
            $this->addError($context);
        }

        return $result;
    }

    /**
     * Checks if context meets the condition requirements.
     *
     * @param mixed $context
     *
     * @return boolean
     */
    abstract protected function isConditionAllowed($context);
}
