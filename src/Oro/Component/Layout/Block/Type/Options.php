<?php

namespace Oro\Component\Layout\Block\Type;

use Symfony\Component\ExpressionLanguage\Expression;

class Options implements \ArrayAccess
{
    /** @var Options */
    private $options;

    /**
     * @param $options
     */
    public function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * @param $optionName
     * @param boolean $shouldBeEvaluated
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException
     */
    public function get($optionName, $shouldBeEvaluated = true)
    {
        if (isset($this->options[$optionName])) {
            $option = $this->options[$optionName];
            if ($shouldBeEvaluated && $option instanceof Expression) {
                throw new \InvalidArgumentException(
                    sprintf('Option "%s" can`t be expression.', $optionName)
                );
            }
            return $option;
        }

        throw new \OutOfBoundsException(sprintf('Argument "%s" not found.', $optionName));
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->options[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->options[] = $value;
        } else {
            $this->options[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if (isset($this->options[$offset])) {
            unset($this->options[$offset]);
        } else {
            throw new \OutOfBoundsException(sprintf('Argument "%s" not found.', $offset));
        }
    }

    /**
     * @internal
     * @return mixed
     */
    public function getAll()
    {
        return $this->options;
    }
}
