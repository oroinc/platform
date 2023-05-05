<?php

namespace Oro\Bundle\ApiBundle\Collection;

/**
 * The collection that allows associate objects with its scalar keys.
 */
class KeyObjectCollection implements \Countable, \IteratorAggregate
{
    /** @var array [key => object, ...] */
    private array $objects = [];
    /** @var array [object hash => key, ...] */
    private array $keys = [];
    /** @var array [object hash => data, ...] */
    private array $data = [];

    /**
     * Adds an object to the collection.
     *
     * @throws \InvalidArgumentException if the given key is not valid or the given object already exists
     */
    public function add(object $object, mixed $key, mixed $data = null): void
    {
        $this->assertKey($key);

        $hash = spl_object_hash($object);
        if (isset($this->objects[$key])) {
            throw new \InvalidArgumentException(sprintf('An object with the key "%s" is already added.', $key));
        }
        if (isset($this->keys[$hash])) {
            throw new \InvalidArgumentException(sprintf(
                'This object is already added with the key "%s". New key: %s.',
                $this->keys[$hash],
                $key
            ));
        }

        $this->objects[$key] = $object;
        $this->keys[$hash] = $key;
        $this->data[$hash] = $data;
    }

    /**
     * Removes an object by its key from the collection.
     *
     * @throws \InvalidArgumentException if the given key is not valid
     */
    public function removeKey(mixed $key): void
    {
        $this->assertKey($key);

        if (isset($this->objects[$key])) {
            $hash = spl_object_hash($this->objects[$key]);
            unset($this->objects[$key], $this->keys[$hash], $this->data[$hash]);
        }
    }

    /**
     * Removes an object from the collection.
     */
    public function remove(object $object): void
    {
        $hash = spl_object_hash($object);
        if (isset($this->keys[$hash])) {
            $key = $this->keys[$hash];
            unset($this->objects[$key], $this->keys[$hash], $this->data[$hash]);
        }
    }

    /**
     * Removes all objects from the collection.
     */
    public function clear(): void
    {
        $this->objects = [];
        $this->keys = [];
        $this->data = [];
    }

    /**
     * Checks whether the collection does contain any object.
     */
    public function isEmpty(): bool
    {
        return empty($this->objects);
    }

    /**
     * Gets the number of objects in the collection
     */
    public function count(): int
    {
        return \count($this->objects);
    }

    /**
     * Checks whether an object exists in the collection.
     */
    public function contains(object $object): bool
    {
        return isset($this->keys[spl_object_hash($object)]);
    }

    /**
     * Checks whether the collection contains an object with the given key.
     *
     * @throws \InvalidArgumentException if the given key is not valid
     */
    public function containsKey(mixed $key): bool
    {
        $this->assertKey($key);

        return isset($this->objects[$key]);
    }

    /**
     * Gets an object by its key.
     *
     * @throws \InvalidArgumentException if the given key is not valid
     */
    public function get(mixed $key): ?object
    {
        $this->assertKey($key);

        return $this->objects[$key] ?? null;
    }

    /**
     * Gets the key of the given object.
     */
    public function getKey(object $object): mixed
    {
        return $this->keys[spl_object_hash($object)] ?? null;
    }

    /**
     * Gets data are associated with an object.
     */
    public function getData(object $object): mixed
    {
        return $this->data[spl_object_hash($object)] ?? null;
    }

    /**
     * Gets all objects from the collection.
     *
     * @return array [key => object, ...]
     */
    public function getAll(): array
    {
        return $this->objects;
    }

    /**
     * Gets an iterator to get all objects from the collection.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->objects);
    }

    private function assertKey(mixed $key): void
    {
        if (!is_scalar($key)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected $key argument of type "scalar", "%s" given.',
                get_debug_type($key)
            ));
        }
        if (\is_string($key) && '' === trim($key)) {
            throw new \InvalidArgumentException('The $key argument should not be a blank string.');
        }
    }
}
