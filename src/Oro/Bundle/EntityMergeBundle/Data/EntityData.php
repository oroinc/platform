<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Exception\OutOfBoundsException;

class EntityData
{
    /**
     * @var EntityMetadata
     */
    protected $metadata;

    /**
     * @var object[]
     */
    protected $entities = array();

    /**
     * @var object
     */
    protected $masterEntity;

    /**
     * @var FieldData[]
     */
    protected $fields = array();

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
        $this->masterEntity = null;
        $this->entities = array();
        foreach ($entities as $entity) {
            $this->addEntity($entity);
        }

        $masterEntity = reset($entities);
        $this->setMasterEntity($masterEntity);

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
            $this->assertEntityClassMatch($entity);
            $this->entities[] = $entity;
        }

        return $this;
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
     * Get entities by offset
     *
     * @param int $offset
     * @return object
     * @throws OutOfBoundsException
     */
    public function getEntityByOffset($offset)
    {
        $offset = (int)$offset;
        if (!isset($this->entities[$offset])) {
            throw new OutOfBoundsException(sprintf('Illegal offset for getting entity: %d.', $offset));
        }
        return $this->entities[$offset];
    }

    /**
     * Asserts that entity match class in metadata
     *
     * @param object $entity
     * @throws InvalidArgumentException
     */
    protected function assertEntityClassMatch($entity)
    {
        $entityClass = $this->getClassName();
        if (!$entity instanceof $entityClass) {
            throw new InvalidArgumentException(
                sprintf(
                    '$entity should be instance of %s, %s given.',
                    $entityClass,
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }
    }

    /**
     * Get entity class name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->getMetadata()->getClassName();
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

    /**
     * Get master entity
     *
     * @return object
     */
    public function getMasterEntity()
    {
        return $this->masterEntity;
    }

    /**
     * Add field merge data
     *
     * @param FieldMetadata $metadata
     * @return FieldData
     */
    public function addNewField(FieldMetadata $metadata)
    {
        $field = new FieldData($this, $metadata);
        $this->fields[$field->getFieldName()] = $field;

        if ($this->getMasterEntity()) {
            $field->setSourceEntity($this->getMasterEntity());
        }

        return $field;
    }

    /**
     * Checks if field merge data was added
     *
     * @param string $fieldName
     * @return bool
     */
    public function hasField($fieldName)
    {
        return !empty($this->fields[$fieldName]);
    }

    /**
     * Gets field merge data by field name
     *
     * @param string $fieldName
     * @return FieldData
     * @throws InvalidArgumentException If such field is not exist
     */
    public function getField($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            throw new InvalidArgumentException(sprintf('Field "%s" not exist.', $fieldName));
        }
        return $this->fields[$fieldName];
    }

    /**
     * Get all fields
     *
     * @return FieldData[]
     */
    public function getFields()
    {
        return $this->fields;
    }
}
