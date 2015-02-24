<?php

namespace Oro\Component\ConfigExpression\Func;

use Oro\Component\ConfigExpression\Exception;

/**
 * Implements an expression that allows to combine several values in an array.
 */
class GetArray extends AbstractFunction
{
    /** @var array */
    protected $values = [];

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'array';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->values = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        $result = [];
        foreach ($this->values as $key => $value) {
            $result[$key] = $this->resolveValue($context, $value);
        }

        return $result;
    }
}
