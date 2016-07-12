<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\ChainProcessor\ToArrayInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityMetadata implements ToArrayInterface
{
    /** @var string */
    protected $className;

    /** @var bool */
    protected $inherited = false;

    /** @var bool */
    protected $hasIdGenerator = false;

    /** @var string[] */
    protected $identifiers = [];

    /** @var MetaPropertyMetadata[] */
    protected $metaProperties = [];

    /** @var FieldMetadata[] */
    protected $fields = [];

    /** @var AssociationMetadata[] */
    protected $associations = [];

    /** @var ParameterBag|null */
    protected $attributes;

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        if (null !== $this->attributes) {
            $attributes = ConfigUtil::cloneItems($this->attributes->toArray());
            $this->attributes->clear();
            foreach ($attributes as $key => $value) {
                $this->attributes->set($key, $value);
            }
        }
        $this->metaProperties = ConfigUtil::cloneObjects($this->metaProperties);
        $this->fields = ConfigUtil::cloneObjects($this->fields);
        $this->associations = ConfigUtil::cloneObjects($this->associations);
    }

    /**
     * Gets a native PHP array representation of the object.
     *
     * @return array [key => value, ...]
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function toArray()
    {
        $result = [];
        if (null !== $this->attributes) {
            $result = $this->attributes->toArray();
        }
        if ($this->className) {
            $result['class'] = $this->className;
        }
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
        $metaProperties = $this->convertPropertiesToArray($this->metaProperties);
        if (!empty($metaProperties)) {
            $result['meta_properties'] = $metaProperties;
        }
        $fields = $this->convertPropertiesToArray($this->fields);
        if (!empty($fields)) {
            $result['fields'] = $fields;
        }
        $associations = $this->convertPropertiesToArray($this->associations);
        if (!empty($associations)) {
            $result['associations'] = $associations;
        }

        return $result;
    }

    /**
     * @param ToArrayInterface[] $properties
     *
     * @return array
     */
    protected function convertPropertiesToArray(array $properties)
    {
        $result = [];
        foreach ($properties as $name => $property) {
            $data = $property->toArray();
            unset($data['name']);
            $result[$name] = $data;
        }

        return $result;
    }

    /**
     * Gets FQCN of an entity.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Sets FQCN of an entity.
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * Gets identifier field names.
     *
     * @return string[]
     */
    public function getIdentifierFieldNames()
    {
        return $this->identifiers;
    }

    /**
     * Sets identifier field names.
     *
     * @param string[] $fieldNames
     */
    public function setIdentifierFieldNames(array $fieldNames)
    {
        $this->identifiers = $fieldNames;
    }

    /**
     * Whether the entity has some strategy to generate identifier value.
     *
     * @return bool
     */
    public function hasIdentifierGenerator()
    {
        return $this->hasIdGenerator;
    }

    /**
     * Sets a flag indicates whether the entity has some strategy to generate identifier value.
     *
     * @param bool $hasIdentifierGenerator
     */
    public function setHasIdentifierGenerator($hasIdentifierGenerator)
    {
        $this->hasIdGenerator = $hasIdentifierGenerator;
    }

    /**
     * Whether an entity is inherited object.
     * It can be an entity implemented by Doctrine table inheritance
     * or by another feature, for example by associations provided by OroPlatform.
     *
     * @return bool
     */
    public function isInheritedType()
    {
        return $this->inherited;
    }

    /**
     * Sets inheritance flag.
     *
     * @param bool $inherited
     */
    public function setInheritedType($inherited)
    {
        $this->inherited = $inherited;
    }

    /**
     * Checks whether metadata of the given field or association exists.
     *
     * @param string $propertyName
     *
     * @return bool
     */
    public function hasProperty($propertyName)
    {
        return
            $this->hasField($propertyName)
            || $this->hasAssociation($propertyName)
            || $this->hasMetaProperty($propertyName);
    }

    /**
     * Removes metadata of a field or association.
     *
     * @param string $propertyName
     */
    public function removeProperty($propertyName)
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
     *
     * @param string $oldName
     * @param string $newName
     */
    public function renameProperty($oldName, $newName)
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
     * Gets metadata for all meta properties.
     *
     * @return MetaPropertyMetadata[] [meta property name => MetaPropertyMetadata, ...]
     */
    public function getMetaProperties()
    {
        return $this->metaProperties;
    }

    /**
     * Checks whether metadata of the given meta property exists.
     *
     * @param string $metaPropertyName
     *
     * @return bool
     */
    public function hasMetaProperty($metaPropertyName)
    {
        return isset($this->metaProperties[$metaPropertyName]);
    }

    /**
     * Gets metadata of a meta property.
     *
     * @param string $metaPropertyName
     *
     * @return MetaPropertyMetadata|null
     */
    public function getMetaProperty($metaPropertyName)
    {
        if (!isset($this->metaProperties[$metaPropertyName])) {
            return null;
        }

        return $this->metaProperties[$metaPropertyName];
    }

    /**
     * Adds metadata of a meta property.
     *
     * @param MetaPropertyMetadata $metaProperty
     *
     * @return MetaPropertyMetadata
     */
    public function addMetaProperty(MetaPropertyMetadata $metaProperty)
    {
        $this->metaProperties[$metaProperty->getName()] = $metaProperty;

        return $metaProperty;
    }

    /**
     * Removes metadata of a meta property.
     *
     * @param string $metaPropertyName
     */
    public function removeMetaProperty($metaPropertyName)
    {
        unset($this->metaProperties[$metaPropertyName]);
    }

    /**
     * Renames existing meta property
     *
     * @param string $oldName
     * @param string $newName
     */
    public function renameMetaProperty($oldName, $newName)
    {
        $metadata = $this->getMetaProperty($oldName);
        if (null !== $metadata) {
            $this->removeMetaProperty($oldName);
            $metadata->setName($newName);
            $this->addMetaProperty($metadata);
        }
    }

    /**
     * Gets metadata for all fields.
     *
     * @return FieldMetadata[] [field name => FieldMetadata, ...]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Checks whether metadata of the given field exists.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Gets metadata of a field.
     *
     * @param string $fieldName
     *
     * @return FieldMetadata|null
     */
    public function getField($fieldName)
    {
        if (!isset($this->fields[$fieldName])) {
            return null;
        }

        return $this->fields[$fieldName];
    }

    /**
     * Adds metadata of a field.
     *
     * @param FieldMetadata $field
     *
     * @return FieldMetadata
     */
    public function addField(FieldMetadata $field)
    {
        $this->fields[$field->getName()] = $field;

        return $field;
    }

    /**
     * Removes metadata of a field.
     *
     * @param string $fieldName
     */
    public function removeField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * Renames existing field
     *
     * @param string $oldName
     * @param string $newName
     */
    public function renameField($oldName, $newName)
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
    public function getAssociations()
    {
        return $this->associations;
    }

    /**
     * Checks whether metadata of the given association exists.
     *
     * @param string $associationName
     *
     * @return bool
     */
    public function hasAssociation($associationName)
    {
        return isset($this->associations[$associationName]);
    }

    /**
     * Gets metadata of an association.
     *
     * @param string $associationName
     *
     * @return AssociationMetadata|null
     */
    public function getAssociation($associationName)
    {
        if (!isset($this->associations[$associationName])) {
            return null;
        }

        return $this->associations[$associationName];
    }

    /**
     * Adds metadata of an association.
     *
     * @param AssociationMetadata $association
     *
     * @return AssociationMetadata
     */
    public function addAssociation(AssociationMetadata $association)
    {
        $this->associations[$association->getName()] = $association;

        return $association;
    }

    /**
     * Removes metadata of an association.
     *
     * @param string $associationName
     */
    public function removeAssociation($associationName)
    {
        unset($this->associations[$associationName]);
    }

    /**
     * Renames existing association
     *
     * @param string $oldName
     * @param string $newName
     */
    public function renameAssociation($oldName, $newName)
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
     *
     * @param string $attributeName
     *
     * @return bool
     */
    public function has($attributeName)
    {
        return null !== $this->attributes && $this->attributes->has($attributeName);
    }

    /**
     * Gets an additional attribute.
     *
     * @param string $attributeName
     *
     * @return mixed|null
     */
    public function get($attributeName)
    {
        if (null === $this->attributes) {
            return null;
        }

        return $this->attributes->get($attributeName);
    }

    /**
     * Sets an additional attribute.
     *
     * @param string $attributeName
     * @param mixed  $value
     */
    public function set($attributeName, $value)
    {
        if (null === $this->attributes) {
            $this->attributes = new ParameterBag();
        }
        $this->attributes->set($attributeName, $value);
    }

    /**
     * Removes an additional attribute.
     *
     * @param string $attributeName
     */
    public function remove($attributeName)
    {
        if (null !== $this->attributes) {
            $this->attributes->remove($attributeName);
        }
    }
}
