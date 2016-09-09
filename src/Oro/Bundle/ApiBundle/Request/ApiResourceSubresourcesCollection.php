<?php

namespace Oro\Bundle\ApiBundle\Request;

class ApiResourceSubresourcesCollection implements \Countable, \IteratorAggregate
{
    /** @var ApiResourceSubresources[] [entity class => ApiResourceSubresources, ...] */
    protected $resources = [];

    /**
     * Checks whether a resource for a given entity exists in the collection.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function has($entityClass)
    {
        return isset($this->resources[$entityClass]);
    }

    /**
     * Gets the resource by entity class name.
     *
     * @param string $entityClass
     *
     * @return ApiResourceSubresources|null
     */
    public function get($entityClass)
    {
        return isset($this->resources[$entityClass])
            ? $this->resources[$entityClass]
            : null;
    }

    /**
     * Adds a resource to the collection.
     *
     * @param ApiResourceSubresources $resource
     *
     * @throws \InvalidArgumentException if a resource for the same entity already exists in the collection
     */
    public function add(ApiResourceSubresources $resource)
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
     * @return ApiResourceSubresources|null The removed resource or NULL,
     *                                      if the collection did not contain the resource.
     */
    public function remove($entityClass)
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
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty()
    {
        return empty($this->resources);
    }

    /**
     * Clears the collection, removing all elements.
     *
     * @return void
     */
    public function clear()
    {
        $this->resources = [];
    }

    /**
     * Gets a native PHP array representation of the collection.
     *
     * @return ApiResourceSubresources[] [entity class => ApiResourceSubresources, ...]
     */
    public function toArray()
    {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->resources);
    }
}
