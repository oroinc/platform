<?php

namespace Oro\Component\Action\Model;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractStorage implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var bool
     */
    protected $modified;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->modified = false;
    }

    /**
     * Set value
     *
     * @param string $name
     * @param mixed $value
     * @return AbstractStorage
     */
    public function set($name, $value)
    {
        if (!array_key_exists($name, $this->data) || $this->data[$name] !== $value) {
            $this->data[$name] = $value;
            $this->modified = true;
        }

        return $this;
    }

    /**
     * Add values
     *
     * @param array $data
     * @return AbstractStorage
     */
    public function add(array $data)
    {
        foreach ($data as $name => $value) {
            $this->set($name, $value);
        }

        return $this;
    }

    /**
     * Get value
     *
     * @param string $name
     * @return mixed $value
     */
    public function get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } else {
            return null;
        }
    }

    /**
     * Get data values
     *
     * @param array $names Optional list of names of values that should be filtered
     * @return array
     */
    public function getValues(array $names = [])
    {
        if (!$names) {
            return $this->data;
        }

        $result = [];

        foreach ($names as $name) {
            $result[$name] = $this->get($name);
        }

        return $result;
    }

    /**
     * Has value
     *
     * @param string $name
     * @return boolean
     */
    public function has($name)
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * Remove value by name
     *
     * @param string $name
     * @return AbstractStorage
     */
    public function remove($name)
    {
        if (array_key_exists($name, $this->data)) {
            unset($this->data[$name]);
            $this->modified = true;
        }

        return $this;
    }

    /**
     * Is data empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return 0 === count($this->data);
    }

    /**
     * @return bool
     */
    public function isModified()
    {
        return $this->modified;
    }

    /**
     * This method should be called only by system listeners
     *
     * @param bool $modified
     * @return AbstractStorage
     */
    public function setModified($modified)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
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

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
