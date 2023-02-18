<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A collection of FilterInterface.
 */
class FilterCollection implements \IteratorAggregate, \Countable, \ArrayAccess
{
    private const GROUPED_FILTER_KEY_TEMPLATE = '%s[%s]';

    /** @var FilterInterface[] */
    private array $filters = [];
    /** @var array [filter key => true, ...] */
    private array $excludeFromDefaultGroup = [];
    private ?string $defaultGroupName = null;

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
        return sprintf(self::GROUPED_FILTER_KEY_TEMPLATE, $group, $key);
    }

    /**
     * Gets the name of default filter's group.
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
     * Checks if a filter with the specified key can be included in the default group.
     */
    public function isIncludeInDefaultGroup(string $key): bool
    {
        return !isset($this->excludeFromDefaultGroup[$key]);
    }

    /**
     * Sets a flag indicates whether a filter with the specified key should be included or not in the default group.
     *
     * @param string $key
     * @param bool   $includeInDefaultGroup FALSE if the filter should not be included in the default group
     */
    public function setIncludeInDefaultGroup(string $key, bool $includeInDefaultGroup = true): void
    {
        if (!$includeInDefaultGroup) {
            $this->excludeFromDefaultGroup[$key] = true;
        } elseif (isset($this->excludeFromDefaultGroup[$key])) {
            unset($this->excludeFromDefaultGroup[$key]);
        }
    }

    /**
     * Checks whether the collection contains a filter with the specified key.
     * In additional finds the filter in the default filter's group if it is set.
     */
    public function has(string $key): bool
    {
        if (isset($this->filters[$key])) {
            return true;
        }
        if ($this->defaultGroupName && $this->isIncludeInDefaultGroup($key)) {
            return isset($this->filters[$this->getGroupedFilterKey($this->defaultGroupName, $key)]);
        }

        return false;
    }

    /**
     * Gets a filter by its key.
     * In additional finds the filter in the default filter's group if it is set.
     */
    public function get(string $key): ?FilterInterface
    {
        if (isset($this->filters[$key])) {
            return $this->filters[$key];
        }
        if ($this->defaultGroupName && $this->isIncludeInDefaultGroup($key)) {
            $groupedKey = $this->getGroupedFilterKey($this->defaultGroupName, $key);
            if (isset($this->filters[$groupedKey])) {
                return $this->filters[$groupedKey];
            }
        }

        return null;
    }

    /**
     * Sets a filter by key.
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
     * @param bool            $includeInDefaultGroup FALSE if the filter should not be included in the default group
     */
    public function add(string $key, FilterInterface $filter, bool $includeInDefaultGroup = true): void
    {
        $this->filters[$key] = $filter;
        $this->setIncludeInDefaultGroup($key, $includeInDefaultGroup);
    }

    /**
     * Removes a filter by key from the collection.
     */
    public function remove(string $key): void
    {
        unset($this->filters[$key], $this->excludeFromDefaultGroup[$key]);
    }

    /**
     * Checks whether the collection is empty (contains no elements).
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
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset): ?FilterInterface
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return \count($this->filters);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->filters);
    }
}
