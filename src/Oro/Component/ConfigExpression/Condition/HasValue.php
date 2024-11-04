<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Checks whether the value exists in the context.
 */
class HasValue extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /** @var mixed */
    protected $value;

    #[\Override]
    public function getName()
    {
        return 'has';
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
    protected function isConditionAllowed($context)
    {
        if ($this->value instanceof PropertyPathInterface) {
            return $this->contextAccessor->hasValue($context, $this->value);
        }

        return true;
    }
}
