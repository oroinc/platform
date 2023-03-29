<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

class TestEntityWithArrayAccess implements \ArrayAccess
{
    private ?string $typedAttribute = null;
    /** @var array [name => value, ...] */
    private array $attributes = [];

    public function getTypedAttribute(): ?string
    {
        return $this->typedAttribute;
    }

    public function setTypedAttribute(?string $value): void
    {
        $this->typedAttribute = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset): mixed
    {
        if (!\array_key_exists($offset, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf('The "%s" attribute does not exist.', $offset));
        }

        return $this->attributes[$offset];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }
}
