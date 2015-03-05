<?php

namespace Oro\Component\ConfigExpression\Func;

use Oro\Component\ConfigExpression\Exception;

/**
 * Implements 'trim' function.
 */
class Trim extends AbstractFunction
{
    /** @var mixed */
    protected $value;

    /** @var mixed */
    protected $charlist;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'trim';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $params = [$this->value];
        if ($this->charlist !== null) {
            $params[] = $this->charlist;
        }

        return $this->convertToArray($params);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        $params = [$this->value];
        if ($this->charlist !== null) {
            $params[] = $this->charlist;
        }

        return $this->convertToPhpCode($params, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $count = count($options);
        if ($count >= 1 && $count <= 2) {
            $this->value = reset($options);
            if ($count > 1) {
                $this->charlist = next($options);
            }
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 or 2 elements, but %d given.', count($options))
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
            '{{ value }}'    => $this->resolveValue($context, $this->value),
            '{{ charlist }}' => $this->resolveValue($context, $this->charlist)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        $value = $this->resolveValue($context, $this->value);
        if ($value === null) {
            return $value;
        }
        if (!is_string($value)) {
            $this->addError(
                $context,
                sprintf(
                    'Expected a string value, got "%s".',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );

            return $value;
        }

        return $this->charlist
            ? trim($value, $this->resolveValue($context, $this->charlist))
            : trim($value);
    }
}
