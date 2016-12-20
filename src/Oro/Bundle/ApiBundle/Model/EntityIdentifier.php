<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * This class can be used if you need a base class for an association that can
 * contain different types of entities that are not implemented via Doctrine table inheritance
 * and as result do not have a common superclass.
 */
class EntityIdentifier implements \ArrayAccess
{
    /** @var mixed */
    private $id;

    /** @var string */
    private $class;

    /** @var array [name => value, ...] */
    private $attributes = [];

    /**
     * @param mixed|null  $id
     * @param string|null $class
     */
    public function __construct($id = null, $class = null)
    {
        $this->id = $id;
        $this->class = $class;
    }

    /**
     * Gets an identifier of the entity.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets an identifier of the entity.
     *
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the FQCN of the entity.
     *
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Sets the FQCN of the entity.
     *
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Gets all additional attributes.
     *
     * @return array [name => value, ...]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Checks whether an additional attribute exists.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Gets a value of an additional attribute.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException if an attribute does not exist
     */
    public function getAttribute($name)
    {
        if (!array_key_exists($name, $this->attributes)) {
            throw new \InvalidArgumentException(sprintf('The "%s" attribute does not exist.', $name));
        }

        return $this->attributes[$name];
    }

    /**
     * Sets an additional attribute.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Removes an additional attribute.
     *
     * @param string $name
     */
    public function removeAttribute($name)
    {
        unset($this->attributes[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->hasAttribute($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->removeAttribute($offset);
    }
}
