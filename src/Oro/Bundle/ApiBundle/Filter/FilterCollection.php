<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A collection of FilterInterface.
 */
class FilterCollection implements \IteratorAggregate, \Countable, \ArrayAccess
{
    private const GROUPED_FILTER_KEY_TEMPLATE = '%s[%s]';

    /** @var FilterInterface[] */
    private $filters = [];

    /** @var string|null */
    private $defaultGroupName;

    /**
     * Builds the filter key for in the given group.
     *
     * @param string $group The name of a filter's group
     * @param string $key   The filter key
     *
     * @return string The filter key in the given group
     */
    public function getGroupedFilterKey(string $group, string $key): string
    {
        return \sprintf(self::GROUPED_FILTER_KEY_TEMPLATE, $group, $key);
    }

    /**
     * Gets the name of default filter's group.
     *
     * @return string|null
     */
    public function getDefaultGroupName(): ?string
    {
        return $this->defaultGroupName;
    }

    /**
     * Sets the name of default filter's group.
     *
     * @param string|null $group The name of a filter's group
     */
    public function setDefaultGroupName(?string $group): void
    {
        $this->defaultGroupName = $group;
    }

    /**
     * Checks whether the collection contains a filter with the specified key.
     * In additional finds the filter in the default filter's group if it is set.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        if (isset($this->filters[$key])) {
            return true;
        }
        if ($this->defaultGroupName) {
            return isset($this->filters[$this->getGroupedFilterKey($this->defaultGroupName, $key)]);
        }

        return false;
    }

    /**
     * Gets a filter by its key.
     * In additional finds the filter in the default filter's group if it is set.
     *
     * @param string $key
     *
     * @return FilterInterface|null A FilterInterface instance or null when not found
     */
    public function get(string $key): ?FilterInterface
    {
        if (isset($this->filters[$key])) {
            return $this->filters[$key];
        }
        if ($this->defaultGroupName) {
            $groupedKey = $this->getGroupedFilterKey($this->defaultGroupName, $key);
            if (isset($this->filters[$groupedKey])) {
                return $this->filters[$groupedKey];
            }
        }

        return null;
    }

    /**
     * Sets a filter by key.
     *
     * @param string          $key
     * @param FilterInterface $filter
     */
    public function set(string $key, FilterInterface $filter): void
    {
        $this->filters[$key] = $filter;
    }

    /**
     * Adds a filter to the collection.
     *
     * @param string          $key
     * @param FilterInterface $filter
     */
    public function add(string $key, FilterInterface $filter): void
    {
        $this->filters[$key] = $filter;
    }

    /**
     * Removes a filter by key from the collection.
     *
     * @param string $key
     */
    public function remove(string $key): void
    {
        unset($this->filters[$key]);
    }

    /**
     * Checks whether the collection is empty (contains no elements).
     *
     * @return bool TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty(): bool
    {
        return empty($this->filters);
    }

    /**
     * Returns all filters in this collection.
     *
     * @return FilterInterface[]
     */
    public function all(): array
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
        return \count($this->filters);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->filters);
    }
}
