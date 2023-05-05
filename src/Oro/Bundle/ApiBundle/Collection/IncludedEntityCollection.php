<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * The collection of additional entities included into API request for such actions
 * as "create", "update", "update_subresource", etc.
 */
class IncludedEntityCollection implements \Countable, \IteratorAggregate
{
    private KeyObjectCollection $collection;
    /** @var array [key => [entity class, entity id], ...] */
    private array $keys = [];
    /** @var array|null [entity class, entity id, entity, metadata] */
    private ?array $primaryEntity = null;

    public function __construct()
    {
        $this->collection = new KeyObjectCollection();
    }

    /**
     * Sets the primary entity identifier.
     */
    public function setPrimaryEntityId(string $entityClass, mixed $entityId): void
    {
        $this->primaryEntity = [$entityClass, $entityId, null, null];
    }

    /**
     * Checks whether the given class and id represents the primary entity.
     */
    public function isPrimaryEntity(string $entityClass, mixed $entityId): bool
    {
        return
            null !== $this->primaryEntity
            && null !== $this->primaryEntity[1]
            && $entityClass === $this->primaryEntity[0]
            && $entityId === $this->primaryEntity[1];
    }

    /**
     * Sets the primary entity.
     */
    public function setPrimaryEntity(?object $entity, ?EntityMetadata $metadata): void
    {
        if (null === $this->primaryEntity) {
            throw new \LogicException('The primary entity identifier must be set before.');
        }

        $this->primaryEntity[2] = $entity;
        $this->primaryEntity[3] = $metadata;
    }

    /**
     * Gets the primary entity.
     */
    public function getPrimaryEntity(): ?object
    {
        return null !== $this->primaryEntity
            ? $this->primaryEntity[2]
            : null;
    }

    /**
     * Gets the primary entity.
     */
    public function getPrimaryEntityMetadata(): ?EntityMetadata
    {
        return null !== $this->primaryEntity
            ? $this->primaryEntity[3]
            : null;
    }

    /**
     * Adds an entity to the collection.
     */
    public function add(object $entity, string $entityClass, mixed $entityId, IncludedEntityData $data): void
    {
        $key = $this->buildKey($entityClass, $entityId);
        $this->collection->add($entity, $key, $data);
        $this->keys[$key] = [$entityClass, $entityId];
    }

    /**
     * Removes an entity from the collection.
     */
    public function remove(string $entityClass, mixed $entityId): void
    {
        $key = $this->buildKey($entityClass, $entityId);
        $this->collection->removeKey($key);
        unset($this->keys[$key]);
    }

    /**
     * Removes all entities from the collection.
     */
    public function clear(): void
    {
        $this->collection->clear();
        $this->keys = [];
    }

    /**
     * Checks whether the collection does contain any entity.
     */
    public function isEmpty(): bool
    {
        return $this->collection->isEmpty();
    }

    /**
     * Gets the number of entities in the collection
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Checks whether an entity exists in the collection.
     */
    public function contains(string $entityClass, mixed $entityId): bool
    {
        return $this->collection->containsKey($this->buildKey($entityClass, $entityId));
    }

    /**
     * Gets an entity.
     */
    public function get(string $entityClass, mixed $entityId): ?object
    {
        return $this->collection->get($this->buildKey($entityClass, $entityId));
    }

    /**
     * Gets data are associated with an object.
     */
    public function getData(object $object): ?IncludedEntityData
    {
        return $this->collection->getData($object);
    }

    /**
     * Gets a class is associated with an object.
     */
    public function getClass(object $object): ?string
    {
        $key = $this->collection->getKey($object);

        return null !== $key && \array_key_exists($key, $this->keys)
            ? $this->keys[$key][0]
            : null;
    }

    /**
     * Gets an identifier is associated with an object.
     */
    public function getId(object $object): mixed
    {
        $key = $this->collection->getKey($object);

        return null !== $key && \array_key_exists($key, $this->keys)
            ? $this->keys[$key][1]
            : null;
    }

    /**
     * Gets all entities from the collection.
     *
     * @return object[]
     */
    public function getAll(): array
    {
        return array_values($this->collection->getAll());
    }

    /**
     * Gets an iterator to get all objects from the collection.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_values($this->collection->getAll()));
    }

    private function buildKey(string $entityClass, mixed $entityId): string
    {
        return $entityClass . ':' . (string)$entityId;
    }
}
