<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\Exception;

/**
 * Implements logical TRUE constant.
 */
class TrueCondition extends AbstractCondition
{
    #[\Override]
    public function getName()
    {
        return 'true';
    }

    #[\Override]
    public function toArray()
    {
        return $this->convertToArray(null);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode(null, $factoryAccessor);
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (!empty($options)) {
            throw new Exception\InvalidArgumentException('Options are prohibited.');
        }

        return $this;
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        return true;
    }
}
