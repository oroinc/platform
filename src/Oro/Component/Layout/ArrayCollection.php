<?php

namespace Oro\Component\Layout;

class ArrayCollection implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $elements;

    /**
     * @param array $elements
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->elements[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        if (isset($this->elements[$offset])) {
            return $this->elements[$offset];
        };

        throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $offset));
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Not supported');
    }
}
