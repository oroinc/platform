<?php

namespace Oro\Bundle\ReminderBundle\Model;

class ReminderState implements \ArrayAccess, \Serializable
{
    /**
     * @var array
     */
    protected $types;

    /**
     * @param array $types
     */
    public function __construct(array $types = [])
    {
        $this->types = $types;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->types;
    }

    /**
     * {inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->types);
    }

    /**
     * {inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->types = unserialize($serialized);
    }

    /**
     * {inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->types[$offset]);
    }

    /**
     * {inheritdoc}
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->types[$offset];
        }

        return null;
    }

    /**
     * {inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->types[$offset] = $value;
    }

    /**
     * {inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->types[$offset]);
        }
    }
}
