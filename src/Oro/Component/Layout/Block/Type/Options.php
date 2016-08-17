<?php

namespace Oro\Component\Layout\Block\Type;

use Symfony\Component\ExpressionLanguage\Expression;

class Options implements \ArrayAccess
{
    /** @var Options */
    private $options;

    /**
     * {@inheritdoc}
     */
    public function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function get($optionName, $shouldBeEvaluated = true)
    {
        if ($this->hasArgument($optionName)) {
            $option = $this->options[$optionName];
            if ($shouldBeEvaluated && $option instanceof Expression) {
                throw new \InvalidArgumentException(
                    sprintf('Option "%s" can`t be expression.', $optionName)
                );
            }
            return $option;
        }

        throw new \InvalidArgumentException(sprintf('Argument "%s" not found.', $optionName));
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
        if ($this->hasArgument($offset)) {
            unset($this->options[$offset]);
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

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function hasArgument($offset)
    {
        return array_key_exists($offset, $this->options);
    }
}
