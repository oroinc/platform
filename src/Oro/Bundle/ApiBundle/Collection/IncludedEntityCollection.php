<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * The collection of additional entities included into API request for such actions
 * as "create", "update", "update_subresource", etc.
 */
class IncludedEntityCollection implements \Countable, \IteratorAggregate
{
    /** @var KeyObjectCollection */
    private $collection;

    /** @var array [key => [entity class, entity id], ...] */
    private $keys = [];

    /** @var array|null [entity class, entity id, entity, metadata] */
    private $primaryEntity;

    public function __construct()
    {
        $this->collection = new KeyObjectCollection();
    }

    /**
     * Sets the primary entity identifier.
     *
     * @param string $entityClass
     * @param mixed  $entityId
     */
    public function setPrimaryEntityId($entityClass, $entityId)
    {
        $this->primaryEntity = [$entityClass, $entityId, null, null];
    }

    /**
     * Checks whether the given class and id represents the primary entity.
     *
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return bool
     */
    public function isPrimaryEntity($entityClass, $entityId)
    {
        return
            null !== $this->primaryEntity
            && null !== $this->primaryEntity[1]
            && $entityClass === $this->primaryEntity[0]
            && $entityId === $this->primaryEntity[1];
    }

    /**
     * Sets the primary entity.
     *
     * @param object|null         $entity
     * @param EntityMetadata|null $metadata
     */
    public function setPrimaryEntity($entity, ?EntityMetadata $metadata)
    {
        if (null === $this->primaryEntity) {
            throw new \LogicException('The primary entity identifier must be set before.');
        }

        $this->primaryEntity[2] = $entity;
        $this->primaryEntity[3] = $metadata;
    }

    /**
     * Gets the primary entity.
     *
     * @return object|null
     */
    public function getPrimaryEntity()
    {
        return null !== $this->primaryEntity
            ? $this->primaryEntity[2]
            : null;
    }

    /**
     * Gets the primary entity.
     *
     * @return EntityMetadata|null
     */
    public function getPrimaryEntityMetadata(): ?EntityMetadata
    {
        return null !== $this->primaryEntity
            ? $this->primaryEntity[3]
            : null;
    }

    /**
     * Adds an entity to the collection.
     *
     * @param object             $entity
     * @param string             $entityClass
     * @param mixed              $entityId
     * @param IncludedEntityData $data
     */
    public function add($entity, $entityClass, $entityId, IncludedEntityData $data)
    {
        $key = $this->buildKey($entityClass, $entityId);
        $this->collection->add($entity, $key, $data);
        $this->keys[$key] = [$entityClass, $entityId];
    }

    /**
     * Removes an entity from the collection.
     *
     * @param string $entityClass
     * @param mixed  $entityId
     */
    public function remove($entityClass, $entityId)
    {
        $key = $this->buildKey($entityClass, $entityId);
        $this->collection->removeKey($key);
        unset($this->keys[$key]);
    }

    /**
     * Removes all entities from the collection.
     */
    public function clear()
    {
        $this->collection->clear();
        $this->keys = [];
    }

    /**
     * Checks whether the collection does contain any entity.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->collection->isEmpty();
    }

    /**
     * Gets the number of entities in the collection
     *
     * @return int
     */
    public function count()
    {
        return $this->collection->count();
    }

    /**
     * Checks whether an entity exists in the collection.
     *
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return bool
     */
    public function contains($entityClass, $entityId)
    {
        return $this->collection->containsKey($this->buildKey($entityClass, $entityId));
    }

    /**
     * Gets an entity.
     *
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object|null
     */
    public function get($entityClass, $entityId)
    {
        return $this->collection->get($this->buildKey($entityClass, $entityId));
    }

    /**
     * Gets data are associated with an object.
     *
     * @param object $object
     *
     * @return IncludedEntityData
     */
    public function getData($object)
    {
        return $this->collection->getData($object);
    }

    /**
     * Gets a class is associated with an object.
     *
     * @param object $object
     *
     * @return string|null
     */
    public function getClass($object)
    {
        $key = $this->collection->getKey($object);

        return null !== $key && array_key_exists($key, $this->keys)
            ? $this->keys[$key][0]
            : null;
    }

    /**
     * Gets an identifier is associated with an object.
     *
     * @param object $object
     *
     * @return mixed|null
     */
    public function getId($object)
    {
        $key = $this->collection->getKey($object);

        return null !== $key && array_key_exists($key, $this->keys)
            ? $this->keys[$key][1]
            : null;
    }

    /**
     * Gets an iterator to get all objects from the collection.
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator(array_values($this->collection->getAll()));
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return string
     */
    private function buildKey($entityClass, $entityId)
    {
        return $entityClass . ':' . (string)$entityId;
    }
}
