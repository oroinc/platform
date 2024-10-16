<?php

namespace Oro\Component\ChainProcessor;

/**
 * A base implementation for containers of key/value pairs.
 */
abstract class AbstractParameterBag implements ParameterBagInterface
{
    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->toArray());
    }

    #[\Override]
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    #[\Override]
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    #[\Override]
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    #[\Override]
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
