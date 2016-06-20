<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\Mapping\ClassMetadata;

class EntityMetadata
{
    /** @var ClassMetadata */
    protected $metadata;

    /**
     * @param ClassMetadata $metadata
     */
    public function __construct(ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Returns entity field names
     *
     * @return string[]
     */
    public function getFieldNames()
    {
        return $this->metadata->getFieldNames();
    }

    /**
     * Returns entity association names
     *
     * @return string[]
     */
    public function getAssociationNames()
    {
        return $this->metadata->getAssociationNames();
    }

    /**
     * Returns names of entity identifier fields
     *
     * @return string[]
     */
    public function getIdentifierFieldNames()
    {
        return $this->metadata->getIdentifierFieldNames();
    }

    /**
     * Returns the name of entity identifier field if an entity has a single-field identifier
     *
     * @return string
     */
    public function getSingleIdentifierFieldName()
    {
        return $this->metadata->getSingleIdentifierFieldName();
    }

    /**
     * Checks whether an entity is participate in an inheritance hierarchy,
     * e.g. in the JOINED or SINGLE_TABLE inheritance mapping
     *
     * @return bool
     */
    public function hasInheritance()
    {
        return $this->metadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE;
    }

    /**
     * Returns the discriminator value of the given entity class
     * This does only apply to the JOINED and SINGLE_TABLE inheritance mapping strategies
     * where a discriminator column is used
     *
     * @param string $entityClass
     *
     * @return mixed
     */
    public function getDiscriminatorValue($entityClass)
    {
        $map = array_flip($this->metadata->discriminatorMap);

        return $map[$entityClass];
    }

    /**
     * Gets the type of a field
     *
     * @param string $fieldName
     *
     * @return string|null
     */
    public function getFieldType($fieldName)
    {
        return $this->metadata->getTypeOfField($fieldName);
    }

    /**
     * Returns the mapping of an association
     *
     * @param string $fieldName
     *
     * @return array
     */
    public function getAssociationMapping($fieldName)
    {
        return $this->metadata->getAssociationMapping($fieldName);
    }

    /**
     * Returns the target class name of an association
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function getAssociationTargetClass($fieldName)
    {
        return $this->metadata->getAssociationTargetClass($fieldName);
    }

    /**
     * Checks if the given field is a mapped property
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function isField($fieldName)
    {
        return $this->metadata->hasField($fieldName);
    }

    /**
     * Checks if the given field is a mapped association
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function isAssociation($fieldName)
    {
        return $this->metadata->hasAssociation($fieldName);
    }

    /**
     * Checks if the given field is mapped as collection-valued association
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        if (!$this->isAssociation($fieldName)) {
            return false;
        }

        $mapping = $this->getAssociationMapping($fieldName);

        return
            $mapping['type'] === ClassMetadata::MANY_TO_MANY
            || (!$mapping['isOwningSide'] && $mapping['type'] === ClassMetadata::ONE_TO_MANY);
    }
}
