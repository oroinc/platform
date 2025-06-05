<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

/**
 * Checks array or collection has no any elements.
 */
class NoElements extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /** @var mixed */
    protected $value;

    #[\Override]
    public function getName()
    {
        return 'no_elements';
    }

    #[\Override]
    public function toArray()
    {
        return $this->convertToArray($this->value);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->value, $factoryAccessor);
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (1 === count($options)) {
            $this->value = reset($options);
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 element, but %d given.', count($options))
            );
        }

        return $this;
    }

    #[\Override]
    protected function getMessageParameters($context)
    {
        return [
            '{{ value }}' => $this->resolveValue($context, $this->value)
        ];
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        $value = $this->resolveValue($context, $this->value, false);

        if (is_countable($value)) {
            return count($value) === 0;
        }

        return false;
    }
}
