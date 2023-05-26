<?php

namespace Oro\Bundle\ApiBundle\Collection;

/**
 * The collection of additional entities involved into API request processing.
 */
class AdditionalEntityCollection
{
    /** @var array [entity hash => entity, ...] */
    private array $entities = [];
    /** @var array [entity hash => false for new or existing entity, true for entity to be removed, ...] */
    private array $entityStates = [];

    /**
     * Adds an entity to the collection.
     */
    public function add(object $entity, bool $toBeRemoved = false): void
    {
        $key = spl_object_hash($entity);
        $this->entities[$key] = $entity;
        $this->entityStates[$key] = $toBeRemoved;
    }

    /**
     * Removes an entity from the collection.
     */
    public function remove(object $entity): void
    {
        $key = spl_object_hash($entity);
        unset($this->entities[$key], $this->entityStates[$key]);
    }

    /**
     * Checks whether it was requested to remove an entity from the database.
     */
    public function shouldEntityBeRemoved(object $entity): bool
    {
        return $this->entityStates[spl_object_hash($entity)] ?? false;
    }

    /**
     * Gets an array contains all entities from the collection.
     */
    public function getEntities(): array
    {
        return array_values($this->entities);
    }

    /**
     * Checks whether the collection does contain any entity.
     */
    public function isEmpty(): bool
    {
        return !$this->entities;
    }

    /**
     * Removes all entities from the collection.
     */
    public function clear(): void
    {
        $this->entities = [];
        $this->entityStates = [];
    }
}
