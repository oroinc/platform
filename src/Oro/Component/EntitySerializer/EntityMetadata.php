<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * A wrapper for a manageable entity metadata.
 */
class EntityMetadata
{
    private ClassMetadata $metadata;

    public function __construct(ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Gets entity field names.
     *
     * @return string[]
     */
    public function getFieldNames(): array
    {
        return $this->metadata->getFieldNames();
    }

    /**
     * Gets entity association names.
     *
     * @return string[]
     */
    public function getAssociationNames(): array
    {
        return $this->metadata->getAssociationNames();
    }

    /**
     * Gets names of entity identifier fields.
     *
     * @return string[]
     */
    public function getIdentifierFieldNames(): array
    {
        return $this->metadata->getIdentifierFieldNames();
    }

    /**
     * Gets the name of entity identifier field if an entity has a single field identifier.
     */
    public function getSingleIdentifierFieldName(): string
    {
        return $this->metadata->getSingleIdentifierFieldName();
    }

    /**
     * Checks whether an entity is participate in an inheritance hierarchy,
     * e.g. in the JOINED or SINGLE_TABLE inheritance mapping.
     */
    public function hasInheritance(): bool
    {
        return !$this->metadata->isInheritanceTypeNone();
    }

    /**
     * Gets the discriminator value of the given entity class.
     * This does only apply to the JOINED and SINGLE_TABLE inheritance mapping strategies
     * where a discriminator column is used.
     */
    public function getDiscriminatorValue(string $entityClass): mixed
    {
        $map = array_flip($this->metadata->discriminatorMap);

        return $map[$entityClass];
    }

    /**
     * Gets the data type of the given field.
     */
    public function getFieldType(string $fieldName): ?string
    {
        return $this->metadata->getTypeOfField($fieldName);
    }

    /**
     * Gets the mapping of the given association.
     */
    public function getAssociationMapping(string $fieldName): array
    {
        return $this->metadata->getAssociationMapping($fieldName);
    }

    /**
     * Gets the target class name for the given association.
     */
    public function getAssociationTargetClass(string $fieldName): string
    {
        return $this->metadata->getAssociationTargetClass($fieldName);
    }

    /**
     * Checks whether the given field is a mapped property.
     */
    public function isField(string $fieldName): bool
    {
        return $this->metadata->hasField($fieldName);
    }

    /**
     * Checks whether the given field is a mapped association.
     */
    public function isAssociation(string $fieldName): bool
    {
        return $this->metadata->hasAssociation($fieldName);
    }

    /**
     * Checks whether the given field is a mapped single valued association.
     */
    public function isSingleValuedAssociation(string $fieldName): bool
    {
        return $this->isAssociation($fieldName) && !$this->isCollectionValued($fieldName);
    }

    /**
     * Checks whether the given field is a mapped collection valued association.
     */
    public function isCollectionValuedAssociation(string $fieldName): bool
    {
        return $this->isAssociation($fieldName) && $this->isCollectionValued($fieldName);
    }

    private function isCollectionValued(string $fieldName): bool
    {
        $mapping = $this->getAssociationMapping($fieldName);

        return
            $mapping['type'] === ClassMetadata::MANY_TO_MANY
            || (!$mapping['isOwningSide'] && $mapping['type'] === ClassMetadata::ONE_TO_MANY);
    }
}
