<?php

namespace Oro\Component\Layout;

/**
 * Stores block views collection
 */
class BlockViewCollection implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $elements;

    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->elements[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset): mixed
    {
        if (isset($this->elements[$offset])) {
            return $this->elements[$offset];
        };

        throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $offset));
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('Not supported');
    }
}
