<?php

namespace Oro\Bundle\ApiBundle\Batch\Model;

/**
 * Represents a collection of entities affected by a batch operation.
 */
final class BatchAffectedEntities
{
    private array $primaryEntities = [];
    private array $includedEntities = [];

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
}
