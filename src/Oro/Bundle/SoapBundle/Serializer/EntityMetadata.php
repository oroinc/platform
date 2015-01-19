<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Doctrine\ORM\Mapping\ClassMetadata as ClassMetadata;

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
     * @return boolean
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
     * @return boolean
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
     * @return boolean
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
