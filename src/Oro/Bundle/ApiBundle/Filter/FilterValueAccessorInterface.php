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
     * Gets all filter values with the given key.
     * In additional finds filter values in the default filter's group if it is set.
     *
     * @return FilterValue[]
     */
    public function get(string $key): array;

    /**
     * Gets a filter value by its key. The last value is returned if there are several values with the given key.
     * In additional finds the filter value in the default filter's group if it is set.
     */
    public function getOne(string $key): ?FilterValue;

    /**
     * Gets all filter values from the given group.
     *
     * @param string $group
     *
     * @return array [filter key => [FilterValue, ...], ...]
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
     * @return array [filter key => [FilterValue, ...], ...]
     */
    public function getAll(): array;

    /**
     * Sets a filter value.
     * Removes all filter values with the given key if the given filter value is NULL.
     */
    public function set(string $key, ?FilterValue $value): void;

    /**
     * Removes all filter values with the given key.
     */
    public function remove(string $key): void;
}
