<?php

namespace Oro\Component\ChainProcessor;

class ParameterBag implements \Countable, \ArrayAccess
{
    /** @var array */
    protected $items = [];

    /**
     * @param string $key The name of a parameter
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * @param string $key The name of a parameter
     *
     * @return mixed|null
     */
    public function get($key)
    {
        return array_key_exists($key, $this->items)
            ? $this->items[$key]
            : null;
    }

    /**
     * @param string $key   The name of a parameter
     * @param mixed  $value The value of a parameter
     *
     * @return mixed|null
     */
    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * @param string $key The name of a parameter
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Gets a native PHP array representation of the bag.
     *
     * @return array [key => value, ...]
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * Removes all parameters from the bag.
     *
     * @return void
     */
    public function clear()
    {
        $this->items = [];
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
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
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}
