<?php

namespace Oro\Component\ChainProcessor;

/**
 * A base implementation for containers of key/value pairs.
 */
abstract class AbstractParameterBag implements ParameterBagInterface
{
    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
