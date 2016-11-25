<?php

namespace Oro\Bundle\ApiBundle\Collection;

/**
 * The collection that allows associate objects with its scalar keys.
 */
class KeyObjectCollection implements \Countable, \IteratorAggregate
{
    /** @var array [key => object, ...] */
    private $objects = [];

    /** @var array [object hash => key, ...] */
    private $keys = [];

    /** @var array [object hash => data, ...] */
    private $data = [];

    /**
     * Adds an object to the collection.
     *
     * @param object $object The object to add
     * @param mixed  $key    The scalar key to be associated with the object
     * @param mixed  $data   The data to be associated with the object
     *
     * @throws \InvalidArgumentException if arguments are not valid or the given object already exists
     */
    public function add($object, $key, $data = null)
    {
        $this->assertObject($object);
        $this->assertKey($key);

        $hash = spl_object_hash($object);
        if (isset($this->objects[$key])) {
            throw new \InvalidArgumentException(
                sprintf('An object with the key "%s" is already added.', $key)
            );
        }
        if (isset($this->keys[$hash])) {
            throw new \InvalidArgumentException(
                sprintf('This object is already added with the key "%s". New key: %s.', $this->keys[$hash], $key)
            );
        }

        $this->objects[$key] = $object;
        $this->keys[$hash] = $key;
        $this->data[$hash] = $data;
    }

    /**
     * Removes an object by its key from the collection.
     *
     * @param mixed $key
     *
     * @throws \InvalidArgumentException if the given key is not valid
     */
    public function removeKey($key)
    {
        $this->assertKey($key);

        if (isset($this->objects[$key])) {
            $hash = spl_object_hash($this->objects[$key]);
            unset($this->objects[$key]);
            unset($this->keys[$hash]);
            unset($this->data[$hash]);
        }
    }

    /**
     * Removes an object from the collection.
     *
     * @param object $object
     *
     * @throws \InvalidArgumentException if the given object is not valid
     */
    public function remove($object)
    {
        $this->assertObject($object);

        $hash = spl_object_hash($object);
        if (isset($this->keys[$hash])) {
            $key = $this->keys[$hash];
            unset($this->objects[$key]);
            unset($this->keys[$hash]);
            unset($this->data[$hash]);
        }
    }

    /**
     * Removes all objects from the collection.
     */
    public function clear()
    {
        $this->objects = [];
        $this->keys = [];
        $this->data = [];
    }

    /**
     * Checks whether the collection does contain any object.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->objects);
    }

    /**
     * Gets the number of objects in the collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->objects);
    }

    /**
     * Checks whether an object exists in the collection.
     *
     * @param object $object
     *
     * @return bool
     *
     * @throws \InvalidArgumentException if the given object is not valid
     */
    public function contains($object)
    {
        $this->assertObject($object);

        return isset($this->keys[spl_object_hash($object)]);
    }

    /**
     * Checks whether the collection contains an object with the given key.
     *
     * @param mixed $key
     *
     * @return bool
     *
     * @throws \InvalidArgumentException if the given key is not valid
     */
    public function containsKey($key)
    {
        $this->assertKey($key);

        return isset($this->objects[$key]);
    }

    /**
     * Gets an object by its key.
     *
     * @param mixed $key
     *
     * @return object|null
     *
     * @throws \InvalidArgumentException if the given key is not valid
     */
    public function get($key)
    {
        $this->assertKey($key);

        return isset($this->objects[$key])
            ? $this->objects[$key]
            : null;
    }

    /**
     * Gets the key of the given object.
     *
     * @param object $object
     *
     * @return object|null
     *
     * @throws \InvalidArgumentException if the given object is not valid
     */
    public function getKey($object)
    {
        $this->assertObject($object);

        $hash = spl_object_hash($object);

        return isset($this->keys[$hash])
            ? $this->keys[$hash]
            : null;
    }

    /**
     * Gets data are associated with an object.
     *
     * @param object $object
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException if the given object is not valid
     */
    public function getData($object)
    {
        $this->assertObject($object);

        $hash = spl_object_hash($object);

        return isset($this->data[$hash])
            ? $this->data[$hash]
            : null;
    }

    /**
     * Gets all objects from the collection.
     *
     * @return array [key => object, ...]
     */
    public function getAll()
    {
        return $this->objects;
    }

    /**
     * Gets an iterator to get all objects from the collection.
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->objects);
    }

    /**
     * @param mixed $key
     */
    private function assertKey($key)
    {
        if (!is_scalar($key)) {
            throw new \InvalidArgumentException(
                sprintf('Expected $key argument of type "scalar", "%s" given.', $this->getValueType($key))
            );
        }
        if (is_string($key) && '' === trim($key)) {
            throw new \InvalidArgumentException('The $key argument should not be a blank string.');
        }
    }

    /**
     * @param object $object
     */
    private function assertObject($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException(
                sprintf('Expected $object argument of type "object", "%s" given.', $this->getValueType($object))
            );
        }
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    private function getValueType($value)
    {
        return is_object($value)
            ? get_class($value)
            : gettype($value);
    }
}
