<?php

namespace Oro\Bundle\ApiBundle\Batch\Model;

/**
 * Represents a collection of entities affected by a batch operation.
 * The merge rules for entities affected by different batch operation chunks
 * are defined in {@see BatchAffectedEntitiesMerger}.
 */
final class BatchAffectedEntities
{
    private array $primaryEntities = [];
    private array $includedEntities = [];
    private array $payload = [];

    /**
     * Gets primary entities affected by a batch operation.
     *
     * @return array [[id, request id, is existing], ...]
     */
    public function getPrimaryEntities(): array
    {
        return $this->primaryEntities;
    }

    /**
     * Gets included entities affected by a batch operation.
     *
     * @return array [[class, id, request id, is existing], ...]
     */
    public function getIncludedEntities(): array
    {
        return $this->includedEntities;
    }

    /**
     * Adds a primary entity ID to the list of entities affected by a batch operation.
     */
    public function addPrimaryEntity(mixed $entityId, mixed $requestId, bool $isExisting): void
    {
        $existingEntityKey = null;
        foreach ($this->primaryEntities as $key => [$existingEntityId]) {
            if ($existingEntityId === $entityId) {
                $existingEntityKey = $key;
                break;
            }
        }
        if (null === $existingEntityKey) {
            $this->primaryEntities[] = [$entityId, $requestId, $isExisting];
        } else {
            $this->primaryEntities[$existingEntityKey] = [$entityId, $requestId, $isExisting];
        }
    }

    /**
     * Adds an included entity to the list of entities affected by a batch operation.
     */
    public function addIncludedEntity(
        string $entityClass,
        mixed $entityId,
        mixed $requestId,
        bool $isExisting
    ): void {
        $existingEntityKey = null;
        foreach ($this->includedEntities as $key => [$existingEntityClass, $existingEntityId]) {
            if ($existingEntityClass === $entityClass && $existingEntityId === $entityId) {
                $existingEntityKey = $key;
                break;
            }
        }
        if (null === $existingEntityKey) {
            $this->includedEntities[] = [$entityClass, $entityId, $requestId, $isExisting];
        } else {
            $this->includedEntities[$existingEntityKey] = [$entityClass, $entityId, $requestId, $isExisting];
        }
    }

    /**
     * Gets domain-specific data related to entities affected by a batch operation.
     *
     * @return array<string, mixed> [key => value, ...]
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Sets a value with the given key to domain-specific data related to entities affected by a batch operation.
     * When the payload already have a value with the given key, it will be replaces with the new value.
     */
    public function setToPayload(string $key, mixed $value): void
    {
        $this->payload[$key] = $value;
    }

    /**
     * Adds a value with the given key to domain-specific data related to entities affected by a batch operation.
     * When the payload already have a value with the given key, the new value will be merged with it.
     */
    public function addToPayload(string $key, mixed $value): void
    {
        $this->payload[$key] = \array_key_exists($key, $this->payload)
            ? BatchAffectedEntitiesMerger::mergePayloadValue($this->payload[$key], $value)
            : $value;
    }

    /**
     * Removes a value with the given key from domain-specific data related to entities affected by a batch operation.
     */
    public function removeFromPayload(string $key): void
    {
        unset($this->payload[$key]);
    }

    /**
     * Gets a native PHP array representation of the collection of entities affected by a batch operation.
     */
    public function toArray(): array
    {
        $result = [];
        $primaryEntities = $this->getPrimaryEntities();
        if ($primaryEntities) {
            $result['primary'] = $primaryEntities;
        }
        $includedEntities = $this->getIncludedEntities();
        if ($includedEntities) {
            $result['included'] = $includedEntities;
        }
        $payload = $this->getPayload();
        if ($payload) {
            $result['payload'] = $payload;
        }

        return $result;
    }
}
