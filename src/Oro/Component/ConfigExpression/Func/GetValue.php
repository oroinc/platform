<?php

namespace Oro\Component\ConfigExpression\Func;

use Oro\Component\ConfigExpression\Exception;

/**
 * Implements an expression that allows to get a value from the context.
 */
class GetValue extends AbstractFunction
{
    /** @var mixed */
    protected $value;

    /** @var mixed */
    protected $default;

    /** @var bool */
    protected $hasDefault = false;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'value';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $params = [$this->value];
        if ($this->hasDefault) {
            $params[] = $this->default;
        }

        return $this->convertToArray($params);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        $params = [$this->value];
        if ($this->hasDefault) {
            $params[] = $this->default;
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
                $this->default    = next($options);
                $this->hasDefault = true;
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
    protected function doEvaluate($context)
    {
        if ($this->hasDefault) {
            $value = $this->resolveValue($context, $this->value, false);

            return null !== $value
                ? $value
                : $this->resolveValue($context, $this->default);
        }

        return $this->resolveValue($context, $this->value);
    }
}
