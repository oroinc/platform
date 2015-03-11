<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

/**
 * Checks whether the value is empty string or NULL.
 */
class Blank extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /** @var mixed */
    protected $value;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'empty';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->value, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function getMessageParameters($context)
    {
        return [
            '{{ value }}' => $this->resolveValue($context, $this->value)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $value = $this->resolveValue($context, $this->value, false);

        return '' === $value || null === $value;
    }
}
