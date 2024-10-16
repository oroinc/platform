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

    #[\Override]
    public function offsetExists($offset): bool
    {
        return isset($this->elements[$offset]);
    }

    #[\Override]
    public function offsetGet($offset): mixed
    {
        if (isset($this->elements[$offset])) {
            return $this->elements[$offset];
        };

        throw new \OutOfBoundsException(sprintf('Undefined index: %s.', $offset));
    }

    #[\Override]
    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('Not supported');
    }

    #[\Override]
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('Not supported');
    }
}
