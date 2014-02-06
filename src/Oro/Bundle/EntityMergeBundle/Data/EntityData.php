<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class EntityData
{
    /**
     * @var EntityMetadata
     */
    protected $metadata;

    /**
     * @var object[]
     */
    protected $entities;

    /**
     * @var object
     */
    protected $masterEntity;

    /**
     * @var FieldData[]
     */
    protected $fields;

    /**
     * @param EntityMetadata $metadata
     */
    public function __construct(EntityMetadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Get merge metadata
     *
     * @return EntityMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set entities to merge
     *
     * @param object[] $entities
     * @return EntityData
     */
    public function setEntities(array $entities)
    {
        $this->entities = array();
        foreach ($entities as $entity) {
            $this->addEntity($entity);
        }

        return $this;
    }

    /**
     * Add entity to merge
     *
     * @param object $entity
     * @throws InvalidArgumentException
     * @return EntityData
     */
    public function addEntity($entity)
    {
        if (!$this->hasEntity($entity)) {
            $this->entities[] = $entity;
        }
    }

    /**
     * Checks if merge data has entity
     *
     * @param object $entity
     * @return bool
     */
    public function hasEntity($entity)
    {
        foreach ($this->getEntities() as $currentEntity) {
            // @todo Add more reliable comparing based on Doctrine
            if ($entity === $currentEntity) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get entities to merge
     *
     * @return object[]
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return $this->getMetadata()->get('className');
    }

    /**
     * Set master entity. This entity will be used to update values, all other entities will be deleted.
     *
     * @param object $entity
     * @return EntityData
     * @throws InvalidArgumentException
     */
    public function setMasterEntity($entity)
    {
        if (!$this->hasEntity($entity)) {
            throw new InvalidArgumentException('Add entity before setting it as master.');
        }
        $this->masterEntity = $entity;

        return $this;
    }
}
