<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Provides an interface of a collection of the FilterValue objects.
 */
interface FilterValueAccessorInterface extends QueryStringAccessorInterface
{
    /**
     * Checks whether a filter value with the given key exists.
     * In additional finds the filter value in the default filter's group if it is set.
     *
     * @param string $key The key of a filter value
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Gets a filter value by its key.
     * In additional finds the filter value in the default filter's group if it is set.
     *
     * @param string $key The key of a filter value
     *
     * @return FilterValue|null The FilterValue or NULL if it was not found
     */
    public function get(string $key): ?FilterValue;

    /**
     * Gets all filters from the given group.
     *
     * @param string|null $group The name of a filter's group
     *
     * @return FilterValue[] [filter key => FilterValue, ...]
     */
    public function getGroup(string $group): array;

    /**
     * Gets the name of default filter's group.
     *
     * @return string|null
     */
    public function getDefaultGroupName(): ?string;

    /**
     * Sets the name of default filter's group.
     *
     * @param string|null $group The name of a filter's group
     */
    public function setDefaultGroupName(?string $group): void;

    /**
     * Gets all filter values.
     *
     * @return FilterValue[] [filter key => FilterValue, ...]
     */
    public function getAll(): array;

    /**
     * Sets a filter value.
     *
     * @param string           $key   The key of a filter value
     * @param FilterValue|null $value The filter value
     */
    public function set(string $key, ?FilterValue $value): void;

    /**
     * Removes a filter.
     *
     * @param string $key The key of a filter value
     */
    public function remove(string $key): void;
}
