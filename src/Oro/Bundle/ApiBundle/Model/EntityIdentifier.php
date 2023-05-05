<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * This class should be used if you need a base class for an association that can
 * contain different types of entities that are not implemented via Doctrine table inheritance
 * and as result do not have a common superclass.
 */
class EntityIdentifier implements \ArrayAccess
{
    private mixed $id;
    private ?string $class;
    /** @var array [name => value, ...] */
    private array $attributes = [];

    public function __construct(mixed $id = null, ?string $class = null)
    {
        $this->id = $id;
        $this->class = $class;
    }

    /**
     * Gets an identifier of the entity.
     */
    public function getId(): mixed
    {
        return $this->id;
    }

    /**
     * Sets an identifier of the entity.
     */
    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    /**
     * Gets the FQCN of the entity.
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * Sets the FQCN of the entity.
     */
    public function setClass(?string $class): void
    {
        $this->class = $class;
    }

    /**
     * Gets all additional attributes.
     *
     * @return array [name => value, ...]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Checks whether an additional attribute exists.
     */
    public function hasAttribute(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * Gets a value of an additional attribute.
     *
     * @throws \InvalidArgumentException if an attribute does not exist
     */
    public function getAttribute(string $name): mixed
    {
        if (!\array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf('The "%s" attribute does not exist.', $name));
        }

        return $this->attributes[$name];
    }

    /**
     * Sets an additional attribute.
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Removes an additional attribute.
     */
    public function removeAttribute(string $name): void
    {
        unset($this->attributes[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->hasAttribute($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        $this->removeAttribute($offset);
    }
}
