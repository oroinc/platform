<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

/**
 * Checks array or collection has elements.
 */
class HasNotElements extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const ALLOWED_OPTIONS_COUNT = 1;

    /** @var mixed */
    protected $value;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'has_not_elements';
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
        if (self::ALLOWED_OPTIONS_COUNT === count($options)) {
            $this->value = reset($options);
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have ' . self::ALLOWED_OPTIONS_COUNT . ' element, but %d given.', count($options))
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

        return count($value) === 0;
    }
}
