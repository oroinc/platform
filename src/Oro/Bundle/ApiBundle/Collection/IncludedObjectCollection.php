<?php

namespace Oro\Bundle\ApiBundle\Collection;

class IncludedObjectCollection implements \Countable, \IteratorAggregate
{
    /** @var KeyObjectCollection */
    private $collection;

    /** @var array [key => [object class, object id], ...] */
    private $keys = [];

    public function __construct()
    {
        $this->collection = new KeyObjectCollection();
    }

    /**
     * Adds an object to the collection.
     *
     * @param object             $object
     * @param string             $objectClass
     * @param mixed              $objectId
     * @param IncludedObjectData $data
     */
    public function add($object, $objectClass, $objectId, IncludedObjectData $data)
    {
        $key = $this->buildKey($objectClass, $objectId);
        $this->collection->add($object, $key, $data);
        $this->keys[$key] = [$objectClass, $objectId];
    }

    /**
     * Removes an object from the collection.
     *
     * @param string $objectClass
     * @param mixed  $objectId
     */
    public function remove($objectClass, $objectId)
    {
        $key = $this->buildKey($objectClass, $objectId);
        $this->collection->removeKey($key);
        unset($this->keys[$key]);
    }

    /**
     * Removes all objects from the collection.
     */
    public function clear()
    {
        $this->collection->clear();
        $this->keys = [];
    }

    /**
     * Checks whether the collection does contain any object.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->collection->isEmpty();
    }

    /**
     * Gets the number of objects in the collection
     *
     * @return int
     */
    public function count()
    {
        return $this->collection->count();
    }

    /**
     * Checks whether an object exists in the collection.
     *
     * @param string $objectClass
     * @param mixed  $objectId
     *
     * @return bool
     */
    public function contains($objectClass, $objectId)
    {
        return $this->collection->containsKey($this->buildKey($objectClass, $objectId));
    }

    /**
     * Gets an object by its key.
     *
     * @param string $objectClass
     * @param mixed  $objectId
     *
     * @return object|null
     */
    public function get($objectClass, $objectId)
    {
        return $this->collection->get($this->buildKey($objectClass, $objectId));
    }

    /**
     * Gets data are associated with an object.
     *
     * @param object $object
     *
     * @return IncludedObjectData
     */
    public function getData($object)
    {
        return $this->collection->getData($object);
    }

    /**
     * Gets a class is associated with an object.
     *
     * @param object $object
     *
     * @return string|null
     */
    public function getClass($object)
    {
        $key = $this->collection->getKey($object);

        return null !== $key && array_key_exists($key, $this->keys)
            ? $this->keys[$key][0]
            : null;
    }

    /**
     * Gets an identifier is associated with an object.
     *
     * @param object $object
     *
     * @return mixed|null
     */
    public function getId($object)
    {
        $key = $this->collection->getKey($object);

        return null !== $key && array_key_exists($key, $this->keys)
            ? $this->keys[$key][1]
            : null;
    }

    /**
     * Gets an iterator to get all objects from the collection.
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->collection->getAll()));
    }

    /**
     * @param string $objectClass
     * @param mixed  $objectId
     *
     * @return string
     */
    private function buildKey($objectClass, $objectId)
    {
        return $objectClass . ':' . (string)$objectId;
    }
}
