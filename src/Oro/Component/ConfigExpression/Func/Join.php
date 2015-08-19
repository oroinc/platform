<?php

namespace Oro\Component\ConfigExpression\Func;

use Oro\Component\ConfigExpression\Exception;

/**
 * Join array elements with a string.
 * The result of this function is a string containing a string representation
 * of all values in the same order, with the glue string between each element.
 */
class Join extends AbstractFunction
{
    /** @var mixed */
    protected $glue;

    /** @var array */
    protected $values;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'join';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray(array_merge([$this->glue], $this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode(array_merge([$this->glue], $this->values), $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $count = count($options);
        if ($count < 2) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have at least 2 elements, but %d given.', count($options))
            );
        }
        $this->glue   = reset($options);
        $this->values = [];
        while (--$count > 0) {
            $this->values[] = next($options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageParameters($context)
    {
        $values = array_map(
            function ($value) use ($context) {
                return $this->resolveValue($context, $value);
            },
            $this->values
        );

        return [
            '{{ glue }}'   => $this->resolveValue($context, $this->glue),
            '{{ values }}' => implode(', ', $values)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        $glue = $this->resolveValue($context, $this->glue);
        if (!is_string($glue)) {
            $this->addError(
                $context,
                sprintf(
                    'Expected a string value for the glue, got "%s".',
                    is_object($glue) ? get_class($glue) : gettype($glue)
                )
            );

            return '';
        }
        $values = array_map(
            function ($value) use ($context) {
                return $this->resolveValue($context, $value);
            },
            $this->values
        );

        return implode($glue, $values);
    }
}
