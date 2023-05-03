<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ToArrayInterface;
use Oro\Component\PhpUtils\ReflectionUtil;

/**
 * The metadata for an entity.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityMetadata implements ToArrayInterface, EntityIdMetadataInterface
{
    private string $className;
    private bool $inherited = false;
    private bool $hasIdGenerator = false;
    /** @var string[] */
    private array $identifiers = [];
    /** @var MetaPropertyMetadata[] */
    private array $metaProperties = [];
    /** @var LinkMetadataInterface[] */
    private array $links = [];
    /** @var FieldMetadata[] */
    private array $fields = [];
    /** @var AssociationMetadata[] */
    private array $associations = [];
    private ?ParameterBag $attributes = null;
    private ?TargetMetadataAccessorInterface $targetMetadataAccessor = null;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        if (null !== $this->attributes) {
            $attributes = $this->attributes->toArray();
            $this->attributes->clear();
            foreach ($attributes as $key => $val) {
                if (\is_object($val)) {
                    $val = clone $val;
                }
                $this->attributes->set($key, $val);
            }
        }
        $this->metaProperties = ConfigUtil::cloneObjects($this->metaProperties);
        $this->links = ConfigUtil::cloneObjects($this->links);
        $this->fields = ConfigUtil::cloneObjects($this->fields);
        $this->associations = ConfigUtil::cloneObjects($this->associations);
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function toArray(): array
    {
        $result = [];
        if (null !== $this->attributes) {
            $result = $this->attributes->toArray();
        }
        $result['class'] = $this->className;
        if ($this->inherited) {
            $result['inherited'] = $this->inherited;
        }
        if ($this->hasIdGenerator) {
            $result['has_identifier_generator'] = $this->hasIdGenerator;
        }
        $identifiers = $this->getIdentifierFieldNames();
        if (!empty($identifiers)) {
            $result['identifiers'] = $identifiers;
        }
        $metaProperties = ConfigUtil::convertPropertiesToArray($this->metaProperties);
        if (!empty($metaProperties)) {
            $result['meta_properties'] = $metaProperties;
        }
        $links = ConfigUtil::convertPropertiesToArray($this->links);
        if (!empty($links)) {
            $result['links'] = $links;
        }
        $fields = ConfigUtil::convertPropertiesToArray($this->fields);
        if (!empty($fields)) {
            $result['fields'] = $fields;
        }
        $associations = ConfigUtil::convertPropertiesToArray($this->associations);
        if (!empty($associations)) {
            $result['associations'] = $associations;
        }

        return $result;
    }

    /**
     * Sets an accessor to target metadata by a specified target class name.
     * It is used for multi-target associations.
     * @see \Oro\Bundle\ApiBundle\Model\EntityIdentifier
     */
    public function setTargetMetadataAccessor(?TargetMetadataAccessorInterface $targetMetadataAccessor): void
    {
        $this->targetMetadataAccessor = $targetMetadataAccessor;
    }

    /**
     * Gets FQCN of an entity.
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Replaces FQCN of an entity.
     */
    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    /**
     * Gets identifier field names.
     *
     * @return string[]
     */
    public function getIdentifierFieldNames(): array
    {
        return $this->identifiers;
    }

    /**
     * Sets identifier field names.
     *
     * @param string[] $fieldNames
     */
    public function setIdentifierFieldNames(array $fieldNames): void
    {
        $this->identifiers = $fieldNames;
    }

    /**
     * Whether the entity has some strategy to generate identifier value.
     */
    public function hasIdentifierGenerator(): bool
    {
        return $this->hasIdGenerator;
    }

    /**
     * Sets a flag indicates whether the entity has some strategy to generate identifier value.
     */
    public function setHasIdentifierGenerator(bool $hasIdentifierGenerator): void
    {
        $this->hasIdGenerator = $hasIdentifierGenerator;
    }

    /**
     * Whether an entity is inherited object.
     * It can be an entity implemented by Doctrine table inheritance
     * or by another feature, for example by associations provided by OroPlatform.
     */
    public function isInheritedType(): bool
    {
        return $this->inherited;
    }

    /**
     * Sets inheritance flag.
     */
    public function setInheritedType(bool $inherited): void
    {
        $this->inherited = $inherited;
    }

    /**
     * Gets metadata for the given entity class.
     */
    public function getEntityMetadata(string $className): ?EntityMetadata
    {
        if (null === $this->targetMetadataAccessor || $className === $this->className) {
            return null;
        }

        return $this->targetMetadataAccessor->getTargetMetadata($className, null);
    }

    /**
     * Gets the name of the given property in the source entity.
     * Returns NULL if the property does not exist.
     */
    public function getPropertyPath(string $propertyName): ?string
    {
        $property = $this->getProperty($propertyName);
        if (null === $property) {
            return null;
        }

        return $property->getPropertyPath();
    }

    /**
     * Checks whether metadata of the given field, association or meta property exists.
     */
    public function hasProperty(string $propertyName): bool
    {
        return
            $this->hasField($propertyName)
            || $this->hasAssociation($propertyName)
            || $this->hasMetaProperty($propertyName);
    }

    /**
     * Gets a property metadata by its name.
     */
    public function getProperty(string $propertyName): ?PropertyMetadata
    {
        if (isset($this->fields[$propertyName])) {
            return $this->fields[$propertyName];
        }
        if (isset($this->associations[$propertyName])) {
            return $this->associations[$propertyName];
        }
        if (isset($this->metaProperties[$propertyName])) {
            return $this->metaProperties[$propertyName];
        }

        return null;
    }

    /**
     * Removes metadata of a field or association.
     */
    public function removeProperty(string $propertyName): void
    {
        if ($this->hasField($propertyName)) {
            $this->removeField($propertyName);
        } elseif ($this->hasAssociation($propertyName)) {
            $this->removeAssociation($propertyName);
        } elseif ($this->hasMetaProperty($propertyName)) {
            $this->removeMetaProperty($propertyName);
        }
    }

    /**
     * Renames a field or association.
     */
    public function renameProperty(string $oldName, string $newName): void
    {
        if ($this->hasField($oldName)) {
            $this->renameField($oldName, $newName);
        } elseif ($this->hasAssociation($oldName)) {
            $this->renameAssociation($oldName, $newName);
        } elseif ($this->hasMetaProperty($oldName)) {
            $this->renameMetaProperty($oldName, $newName);
        }
    }

    /**
     * Finds a property metadata by the given property path.
     */
    public function getPropertyByPropertyPath(string $propertyPath): ?PropertyMetadata
    {
        $property = $this->getByPropertyPath($this->fields, $propertyPath);
        if (null === $property) {
            $property = $this->getByPropertyPath($this->associations, $propertyPath);
        }
        if (null === $property) {
            $property = $this->getByPropertyPath($this->metaProperties, $propertyPath);
        }

        return $property;
    }

    private function getByPropertyPath(array $properties, string $propertyPath): ?PropertyMetadata
    {
        /** @var PropertyMetadata $property */
        foreach ($properties as $property) {
            if ($property->getPropertyPath() === $propertyPath) {
                return $property;
            }
        }

        return null;
    }

    /**
     * Gets metadata for all meta properties.
     *
     * @return MetaPropertyMetadata[] [meta property name => MetaPropertyMetadata, ...]
     */
    public function getMetaProperties(): array
    {
        return $this->metaProperties;
    }

    /**
     * Checks whether metadata of the given meta property exists.
     */
    public function hasMetaProperty(string $metaPropertyName): bool
    {
        return isset($this->metaProperties[$metaPropertyName]);
    }

    /**
     * Gets metadata of a meta property.
     */
    public function getMetaProperty(string $metaPropertyName): ?MetaPropertyMetadata
    {
        return $this->metaProperties[$metaPropertyName] ?? null;
    }

    /**
     * Adds metadata of a meta property.
     */
    public function addMetaProperty(MetaPropertyMetadata $metaProperty): MetaPropertyMetadata
    {
        $this->metaProperties[$metaProperty->getName()] = $metaProperty;

        return $metaProperty;
    }

    /**
     * Removes metadata of a meta property.
     */
    public function removeMetaProperty(string $metaPropertyName): void
    {
        unset($this->metaProperties[$metaPropertyName]);
    }

    /**
     * Renames existing meta property.
     */
    public function renameMetaProperty(string $oldName, string $newName): void
    {
        $metadata = $this->getMetaProperty($oldName);
        if (null !== $metadata) {
            $this->removeMetaProperty($oldName);
            $metadata->setName($newName);
            $this->addMetaProperty($metadata);
        }
    }

    /**
     * Gets metadata for all links.
     *
     * @return LinkMetadataInterface[] [link name => LinkMetadataInterface, ...]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Checks whether metadata of the given link exists.
     */
    public function hasLink(string $linkName): bool
    {
        return isset($this->links[$linkName]);
    }

    /**
     * Gets metadata of a link.
     */
    public function getLink(string $linkName): ?LinkMetadataInterface
    {
        return $this->links[$linkName] ?? null;
    }

    /**
     * Adds metadata of a link.
     */
    public function addLink(string $name, LinkMetadataInterface $link): LinkMetadataInterface
    {
        $this->links[$name] = $link;

        return $link;
    }

    /**
     * Removes metadata of a link.
     */
    public function removeLink(string $linkName): void
    {
        unset($this->links[$linkName]);
    }

    /**
     * Gets metadata for all fields.
     *
     * @return FieldMetadata[] [field name => FieldMetadata, ...]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Checks whether metadata of the given field exists.
     */
    public function hasField(string $fieldName): bool
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Gets metadata of a field.
     */
    public function getField(string $fieldName): ?FieldMetadata
    {
        return $this->fields[$fieldName] ?? null;
    }

    /**
     * Adds metadata of a field.
     */
    public function addField(FieldMetadata $field): FieldMetadata
    {
        $this->fields[$field->getName()] = $field;

        return $field;
    }

    /**
     * Removes metadata of a field.
     */
    public function removeField(string $fieldName): void
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * Renames existing field
     */
    public function renameField(string $oldName, string $newName): void
    {
        $metadata = $this->getField($oldName);
        if (null !== $metadata) {
            $this->removeField($oldName);
            $metadata->setName($newName);
            $this->addField($metadata);
        }
    }

    /**
     * Gets metadata for all associations.
     *
     * @return AssociationMetadata[] [association name => AssociationMetadata, ...]
     */
    public function getAssociations(): array
    {
        return $this->associations;
    }

    /**
     * Checks whether metadata of the given association exists.
     */
    public function hasAssociation(string $associationName): bool
    {
        return isset($this->associations[$associationName]);
    }

    /**
     * Gets metadata of an association.
     */
    public function getAssociation(string $associationName): ?AssociationMetadata
    {
        return $this->associations[$associationName] ?? null;
    }

    /**
     * Adds metadata of an association.
     */
    public function addAssociation(AssociationMetadata $association): AssociationMetadata
    {
        $this->associations[$association->getName()] = $association;

        return $association;
    }

    /**
     * Removes metadata of an association.
     */
    public function removeAssociation(string $associationName): void
    {
        unset($this->associations[$associationName]);
    }

    /**
     * Renames existing association
     */
    public function renameAssociation(string $oldName, string $newName): void
    {
        $metadata = $this->getAssociation($oldName);
        if (null !== $metadata) {
            $this->removeAssociation($oldName);
            $metadata->setName($newName);
            $this->addAssociation($metadata);
        }
    }

    /**
     * Checks whether an additional attribute exists.
     */
    public function has(string $attributeName): bool
    {
        return null !== $this->attributes && $this->attributes->has($attributeName);
    }

    /**
     * Gets an additional attribute.
     */
    public function get(string $attributeName): mixed
    {
        if (null === $this->attributes) {
            return null;
        }

        return $this->attributes->get($attributeName);
    }

    /**
     * Sets an additional attribute.
     */
    public function set(string $attributeName, mixed $value): void
    {
        if (null === $this->attributes) {
            $this->attributes = new ParameterBag();
        }
        $this->attributes->set($attributeName, $value);
    }

    /**
     * Removes an additional attribute.
     */
    public function remove(string $attributeName): void
    {
        $this->attributes?->remove($attributeName);
    }

    /**
     * Checks whether the metadata has at least one identifier field.
     */
    public function hasIdentifierFields(): bool
    {
        return !empty($this->identifiers);
    }

    /**
     * Checks whether the metadata contains only identifier fields(s).
     */
    public function hasIdentifierFieldsOnly(): bool
    {
        $idFields = $this->getIdentifierFieldNames();
        if (empty($idFields)) {
            return false;
        }

        $fields = $this->getFields();
        if (empty($fields)) {
            return false;
        }

        if (\count($this->getAssociations()) > 0) {
            return false;
        }

        return
            \count($fields) === \count($idFields)
            && \count(array_diff_key($fields, array_flip($idFields))) === 0;
    }

    /**
     * Extracts the identifier value of an entity represented by this metadata.
     *
     * @param object $entity
     *
     * @return mixed The value of identifier field
     *               or array ([field name => value, ...]) if the entity has composite identifier
     */
    public function getIdentifierValue(object $entity): mixed
    {
        $numberOfIdFields = \count($this->identifiers);
        if (0 === $numberOfIdFields) {
            throw new RuntimeException(sprintf(
                'The entity "%s" does not have identifier field(s).',
                $this->className
            ));
        }

        $reflClass = new EntityReflectionClass($entity);
        if ($numberOfIdFields > 1) {
            $result = [];
            foreach ($this->identifiers as $fieldName) {
                $result[$fieldName] = $this->getPropertyValue($entity, $reflClass, $fieldName);
            }

            return $result;
        }

        return $this->getPropertyValue($entity, $reflClass, reset($this->identifiers));
    }

    private function getPropertyValue(object $entity, \ReflectionClass $reflClass, string $fieldName): mixed
    {
        $propertyName = $fieldName;
        $property = $this->getProperty($fieldName);
        if (null !== $property) {
            $propertyName = $property->getPropertyPath();
        }
        $property = ReflectionUtil::getProperty($reflClass, $propertyName);
        if (null === $property) {
            throw new RuntimeException(sprintf(
                'The class "%s" does not have property "%s".',
                $reflClass->name,
                $propertyName
            ));
        }
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }

        return $property->getValue($entity);
    }
}
