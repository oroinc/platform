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
     */
    public function has(string $key): bool;

    /**
     * Gets a filter value by its key.
     * In additional finds the filter value in the default filter's group if it is set.
     */
    public function get(string $key): ?FilterValue;

    /**
     * Gets all filter values from the given group.
     *
     * @param string $group
     *
     * @return FilterValue[] [filter key => FilterValue, ...]
     */
    public function getGroup(string $group): array;

    /**
     * Gets the name of default filter's group.
     */
    public function getDefaultGroupName(): ?string;

    /**
     * Sets the name of default filter's group.
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
     */
    public function set(string $key, ?FilterValue $value): void;

    /**
     * Removes a filter value.
     */
    public function remove(string $key): void;
}
