<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Represents a collection of API resources.
 */
class ApiResourceCollection implements \Countable, \IteratorAggregate
{
    /** @var ApiResource[] [entity class => ApiResource, ...] */
    private array $resources = [];

    /**
     * Checks whether a resource for a given entity exists in the collection.
     */
    public function has(string $entityClass): bool
    {
        return isset($this->resources[$entityClass]);
    }

    /**
     * Gets the resource by entity class name.
     */
    public function get(string $entityClass): ?ApiResource
    {
        return $this->resources[$entityClass] ?? null;
    }

    /**
     * Adds a resource to the collection.
     *
     * @throws \InvalidArgumentException if a resource for the same entity already exists in the collection
     */
    public function add(ApiResource $resource): void
    {
        $entityClass = $resource->getEntityClass();
        if (isset($this->resources[$entityClass])) {
            throw new \InvalidArgumentException(sprintf('A resource for "%s" already exists.', $entityClass));
        }
        $this->resources[$entityClass] = $resource;
    }

    /**
     * Removes the resource for a given entity from the collection.
     *
     * @param string $entityClass
     *
     * @return ApiResource|null The removed resource or NULL, if the collection did not contain the resource.
     */
    public function remove(string $entityClass): ?ApiResource
    {
        $removedResource = null;
        if (isset($this->resources[$entityClass])) {
            $removedResource = $this->resources[$entityClass];
            unset($this->resources[$entityClass]);
        }

        return $removedResource;
    }

    /**
     * Checks whether the collection is empty (contains no elements).
     */
    public function isEmpty(): bool
    {
        return empty($this->resources);
    }

    /**
     * Clears the collection, removing all elements.
     */
    public function clear(): void
    {
        $this->resources = [];
    }

    /**
     * Gets a native PHP array representation of the collection.
     *
     * @return ApiResource[] [entity class => ApiResource, ...]
     */
    public function toArray(): array
    {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->resources);
    }
}
