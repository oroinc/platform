<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A collection of FilterInterface.
 */
class FilterCollection implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /** @var FilterInterface[] */
    private $filters = [];

    /**
     * Checks whether the collection contains a filter with the specified key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->filters[$key]);
    }

    /**
     * Gets a filter by key.
     *
     * @param string $key
     *
     * @return FilterInterface|null A FilterInterface instance or null when not found
     */
    public function get($key)
    {
        return isset($this->filters[$key])
            ? $this->filters[$key]
            : null;
    }

    /**
     * Sets a filter by key.
     *
     * @param string          $key
     * @param FilterInterface $filter
     */
    public function set($key, FilterInterface $filter)
    {
        $this->filters[$key] = $filter;
    }

    /**
     * Adds a filter to the collection.
     *
     * @param string          $key
     * @param FilterInterface $filter
     */
    public function add($key, FilterInterface $filter)
    {
        $this->filters[$key] = $filter;
    }

    /**
     * Removes a filter by key from the collection.
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->filters[$key]);
    }

    /**
     * Checks whether the collection is empty (contains no elements).
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty()
    {
        return empty($this->filters);
    }

    /**
     * Returns all filters in this collection.
     *
     * @return FilterInterface[]
     */
    public function all()
    {
        return $this->filters;
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
        return count($this->filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->filters);
    }
}
