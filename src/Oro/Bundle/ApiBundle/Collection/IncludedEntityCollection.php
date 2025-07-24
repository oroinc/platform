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
    /** @var array|null [entity class, entity id, entity, metadata, request data] */
    private ?array $primaryEntity = null;

    public function __construct()
    {
        $this->collection = new KeyObjectCollection();
    }

    /**
     * Sets the primary entity identifier.
     * The $entityId is an identifier of an entity that was sent in the request.
     */
    public function setPrimaryEntityId(string $entityClass, mixed $entityId): void
    {
        $this->primaryEntity = [$entityClass, $entityId, null, null, null];
    }

    /**
     * Checks whether the given class and id represents the primary entity.
     * The $entityId is an identifier of an entity that was sent in the request.
     */
    public function isPrimaryEntity(string $entityClass, mixed $entityId): bool
    {
        return
            null !== $this->primaryEntity
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
     * Gets request data for the primary entity.
     */
    public function getPrimaryEntityRequestData(): ?array
    {
        return null !== $this->primaryEntity
            ? $this->primaryEntity[4]
            : null;
    }

    /**
     * Sets request data for the primary entity.
     */
    public function setPrimaryEntityRequestData(array $requestData): void
    {
        if (null === $this->primaryEntity) {
            throw new \LogicException('The primary entity identifier must be set before.');
        }

        $this->primaryEntity[4] = $requestData;
    }

    /**
     * Adds an entity to the collection.
     * The $entityId is an identifier of an entity that was sent in the request.
     */
    public function add(object $entity, string $entityClass, mixed $entityId, IncludedEntityData $data): void
    {
        $key = self::buildKey($entityClass, $entityId);
        $this->collection->add($entity, $key, $data);
        $this->keys[$key] = [$entityClass, $entityId];
    }

    /**
     * Removes an entity from the collection.
     * The $entityId is an identifier of an entity that was sent in the request.
     */
    public function remove(string $entityClass, mixed $entityId): void
    {
        $key = self::buildKey($entityClass, $entityId);
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
    #[\Override]
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Checks whether an entity exists in the collection.
     * The $entityId is an identifier of an entity that was sent in the request.
     */
    public function contains(string $entityClass, mixed $entityId): bool
    {
        return $this->collection->containsKey(self::buildKey($entityClass, $entityId));
    }

    /**
     * Gets an entity.
     * The $entityId is an identifier of an entity that was sent in the request.
     */
    public function get(string $entityClass, mixed $entityId): ?object
    {
        return $this->collection->get(self::buildKey($entityClass, $entityId));
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
     * It is an identifier that was sent in the request.
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
    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(array_values($this->collection->getAll()));
    }

    private static function buildKey(string $entityClass, mixed $entityId): string
    {
        return $entityClass . ':' . self::convertEntityIdToString($entityId);
    }

    private static function convertEntityIdToString(mixed $entityId): string
    {
        if (\is_array($entityId)) {
            return json_encode($entityId, JSON_THROW_ON_ERROR);
        }

        return (string)$entityId;
    }
}
