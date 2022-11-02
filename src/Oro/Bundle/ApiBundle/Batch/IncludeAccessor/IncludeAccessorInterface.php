<?php

namespace Oro\Bundle\ApiBundle\Batch\IncludeAccessor;

/**
 * Represents a class that provides an access to included data for a specific request type.
 */
interface IncludeAccessorInterface
{
    /**
     * Gets the data part of the given primary data item.
     */
    public function getPrimaryItemData(array $item): array;

    /**
     * Sets the data part to the given primary data item.
     */
    public function setPrimaryItemData(array &$item, array $data): void;

    /**
     * Gets the type and ID for the given data item.
     *
     * @param array $item
     *
     * @return array [type, id]
     *
     * @throws \InvalidArgumentException if the item identifier cannot be retrieved
     */
    public function getItemIdentifier(array $item): array;

    /**
     * Gets all relationships for the given data item.
     *
     * @param array $item
     *
     * @return array [item key => [type, id], ...]
     */
    public function getRelationships(array $item): array;

    /**
     * Applies a user supplied function that returns a new identifier to every relationship of the given data item.
     *
     * @param array    $item
     * @param callable $callback function (string $type, string $id): mixed|null
     */
    public function updateRelationships(array &$item, callable $callback): void;
}
