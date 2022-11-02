<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The metadata for an entity association.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AssociationMetadata extends PropertyMetadata
{
    private ?string $targetClass = null;
    private ?string $baseTargetClass = null;
    /** @var string[] */
    private array $acceptableTargetClasses = [];
    private bool $allowEmptyAcceptableTargets = true;
    private ?string $associationPath = null;
    private ?string $associationType = null;
    private bool $collection = false;
    private bool $nullable = false;
    private bool $collapsed = false;
    private ?EntityMetadata $targetMetadata = null;
    private ?TargetMetadataAccessorInterface $targetMetadataAccessor = null;
    /** @var MetaAttributeMetadata[] */
    private array $metaProperties = [];
    /** @var LinkMetadataInterface[] */
    private array $links = [];
    /** @var MetaAttributeMetadata[] */
    private array $relationshipMetaProperties = [];
    /** @var LinkMetadataInterface[] */
    private array $relationshipLinks = [];

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        if (null !== $this->targetMetadata) {
            $this->targetMetadata = clone $this->targetMetadata;
        }
        $this->metaProperties = ConfigUtil::cloneObjects($this->metaProperties);
        $this->links = ConfigUtil::cloneObjects($this->links);
        $this->relationshipMetaProperties = ConfigUtil::cloneObjects($this->relationshipMetaProperties);
        $this->relationshipLinks = ConfigUtil::cloneObjects($this->relationshipLinks);
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function toArray(): array
    {
        $result = array_merge(
            parent::toArray(),
            [
                'nullable'         => $this->nullable,
                'collapsed'        => $this->collapsed,
                'association_type' => $this->associationType,
                'collection'       => $this->collection
            ]
        );
        if ($this->targetClass) {
            $result['target_class'] = $this->targetClass;
        }
        if ($this->baseTargetClass) {
            $result['base_target_class'] = $this->baseTargetClass;
        }
        if ($this->acceptableTargetClasses) {
            $result['acceptable_target_classes'] = $this->acceptableTargetClasses;
        } elseif (!$this->allowEmptyAcceptableTargets) {
            $result['reject_empty_acceptable_targets'] = true;
        }
        if (null !== $this->targetMetadata) {
            $result['target_metadata'] = $this->targetMetadata->toArray();
        }
        $metaProperties = ConfigUtil::convertPropertiesToArray($this->metaProperties);
        if (!empty($metaProperties)) {
            $result['meta_properties'] = $metaProperties;
        }
        $links = ConfigUtil::convertPropertiesToArray($this->links);
        if (!empty($links)) {
            $result['links'] = $links;
        }
        $relationshipMetaProperties = ConfigUtil::convertPropertiesToArray($this->relationshipMetaProperties);
        if (!empty($relationshipMetaProperties)) {
            $result['relationship_meta_properties'] = $relationshipMetaProperties;
        }
        $relationshipLinks = ConfigUtil::convertPropertiesToArray($this->relationshipLinks);
        if (!empty($relationshipLinks)) {
            $result['relationship_links'] = $relationshipLinks;
        }

        return $result;
    }

    /**
     * Sets an accessor to target metadata by a specified target class name and association path.
     * It is used for multi-target associations.
     * @see \Oro\Bundle\ApiBundle\Model\EntityIdentifier
     */
    public function setTargetMetadataAccessor(?TargetMetadataAccessorInterface $targetMetadataAccessor): void
    {
        $this->targetMetadataAccessor = $targetMetadataAccessor;
    }

    /**
     * Sets the path from a root entity to the association.
     */
    public function setAssociationPath(?string $associationPath): void
    {
        $this->associationPath = $associationPath;
    }

    /**
     * Gets metadata for the given association target class.
     */
    public function getTargetMetadata(string $targetClassName = null): ?EntityMetadata
    {
        if (null === $this->targetMetadataAccessor
            || !$this->associationPath
            || !$targetClassName
            || $targetClassName === $this->targetClass
        ) {
            return $this->targetMetadata;
        }

        $targetMetadata = $this->targetMetadataAccessor->getTargetMetadata(
            $targetClassName,
            $this->associationPath
        );

        return $targetMetadata ?? $this->targetMetadata;
    }

    /**
     * Sets metadata of the association target.
     */
    public function setTargetMetadata(EntityMetadata $targetMetadata): void
    {
        $this->targetMetadata = $targetMetadata;
    }

    /**
     * Gets FQCN of the association target.
     */
    public function getTargetClassName(): ?string
    {
        return $this->targetClass;
    }

    /**
     * Sets FQCN of the association target.
     */
    public function setTargetClassName(?string $className): void
    {
        $this->targetClass = $className;
    }

    /**
     * Gets FQCN of the association target base class.
     * E.g. if an association is bases on Doctrine's inheritance mapping,
     * the target class will be Oro\Bundle\ApiBundle\Model\EntityIdentifier
     * and the base target class will be a mapped superclass
     * or a parent class for table inheritance association.
     */
    public function getBaseTargetClassName(): ?string
    {
        return $this->baseTargetClass;
    }

    /**
     * Sets FQCN of the association target.
     */
    public function setBaseTargetClassName(?string $className): void
    {
        $this->baseTargetClass = $className;
    }

    /**
     * Gets FQCN of acceptable association targets.
     *
     * @return string[]
     */
    public function getAcceptableTargetClassNames(): array
    {
        return $this->acceptableTargetClasses;
    }

    /**
     * Sets FQCN of acceptable association targets.
     *
     * @param string[] $classNames
     */
    public function setAcceptableTargetClassNames(array $classNames): void
    {
        $this->acceptableTargetClasses = $classNames;
    }

    /**
     * Adds new acceptable association target.
     */
    public function addAcceptableTargetClassName(string $className): void
    {
        if (!\in_array($className, $this->acceptableTargetClasses, true)) {
            $this->acceptableTargetClasses[] = $className;
        }
    }

    /**
     * Removes acceptable association target.
     */
    public function removeAcceptableTargetClassName(string $className): void
    {
        $key = array_search($className, $this->acceptableTargetClasses, true);
        if (false !== $key) {
            unset($this->acceptableTargetClasses[$key]);
            $this->acceptableTargetClasses = array_values($this->acceptableTargetClasses);
        }
    }

    /**
     * Gets a flag indicates how to treat empty acceptable target classes.
     * TRUE means that any entity type should be accepted.
     * FALSE means that any entity type should be rejected.
     */
    public function isEmptyAcceptableTargetsAllowed(): bool
    {
        return $this->allowEmptyAcceptableTargets;
    }

    /**
     * Sets a flag indicates how to treat empty acceptable target classes.
     * TRUE means that any entity type should be accepted.
     * FALSE means that any entity type should be rejected.
     */
    public function setEmptyAcceptableTargetsAllowed(bool $value): void
    {
        $this->allowEmptyAcceptableTargets = $value;
    }

    /**
     * Gets the type of the association.
     * For example "manyToOne" or "manyToMany".
     * @see \Oro\Bundle\EntityExtendBundle\Extend\RelationType
     */
    public function getAssociationType(): ?string
    {
        return $this->associationType;
    }

    /**
     * Sets the type of the association.
     * For example "manyToOne" or "manyToMany".
     * @see \Oro\Bundle\EntityExtendBundle\Extend\RelationType
     */
    public function setAssociationType(?string $associationType): void
    {
        $this->associationType = $associationType;
    }

    /**
     * Whether the association represents "to-many" or "to-one" relationship.
     */
    public function isCollection(): bool
    {
        return $this->collection;
    }

    /**
     * Sets a flag indicates whether the association represents "to-many" or "to-one" relationship.
     */
    public function setIsCollection(bool $value): void
    {
        $this->collection = $value;
    }

    /**
     * Indicates whether a value of the association can be NULL.
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Sets a flag indicates whether a value of the association can be NULL.
     */
    public function setIsNullable(bool $value): void
    {
        $this->nullable = $value;
    }

    /**
     * Indicates whether the association should be collapsed to a scalar.
     */
    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    /**
     * Sets a flag indicates whether the association should be collapsed to a scalar.
     */
    public function setCollapsed(bool $collapsed = true): void
    {
        $this->collapsed = $collapsed;
    }

    /**
     * Gets metadata for all meta properties that applicable
     * for the association value or each value of collection valued association.
     *
     * @return MetaAttributeMetadata[] [meta property name => MetaAttributeMetadata, ...]
     */
    public function getMetaProperties(): array
    {
        return $this->metaProperties;
    }

    /**
     * Checks whether metadata of the given meta property that applicable
     * for the association value or each value of collection valued association exists.
     */
    public function hasMetaProperty(string $metaPropertyName): bool
    {
        return isset($this->metaProperties[$metaPropertyName]);
    }

    /**
     * Gets metadata of a meta property that applicable
     * for the association value or each value of collection valued association.
     */
    public function getMetaProperty(string $metaPropertyName): ?MetaAttributeMetadata
    {
        return $this->metaProperties[$metaPropertyName] ?? null;
    }

    /**
     * Adds metadata of a meta property that applicable
     * for the association value or each value of collection valued association.
     */
    public function addMetaProperty(MetaAttributeMetadata $metaProperty): MetaAttributeMetadata
    {
        $this->metaProperties[$metaProperty->getName()] = $metaProperty;

        return $metaProperty;
    }

    /**
     * Removes metadata of a meta property that applicable
     * for the association value or each value of collection valued association.
     */
    public function removeMetaProperty(string $metaPropertyName): void
    {
        unset($this->metaProperties[$metaPropertyName]);
    }

    /**
     * Gets metadata for all links that applicable
     * for the association value or each value of collection valued association.
     *
     * @return LinkMetadataInterface[] [link name => LinkMetadataInterface, ...]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Checks whether metadata of the given link that applicable
     * for the association value or each value of collection valued association exists.
     */
    public function hasLink(string $linkName): bool
    {
        return isset($this->links[$linkName]);
    }

    /**
     * Gets metadata of a link that applicable
     * for the association value or each value of collection valued association.
     */
    public function getLink(string $linkName): ?LinkMetadataInterface
    {
        return $this->links[$linkName] ?? null;
    }

    /**
     * Adds metadata of a link that applicable
     * for the association value or each value of collection valued association.
     */
    public function addLink(string $name, LinkMetadataInterface $link): LinkMetadataInterface
    {
        $this->links[$name] = $link;

        return $link;
    }

    /**
     * Removes metadata of a link that applicable
     * for the association value or each value of collection valued association.
     */
    public function removeLink(string $linkName): void
    {
        unset($this->links[$linkName]);
    }

    /**
     * Gets metadata for all meta properties that applicable for a whole association.
     *
     * @return MetaAttributeMetadata[] [meta property name => MetaAttributeMetadata, ...]
     */
    public function getRelationshipMetaProperties(): array
    {
        return $this->relationshipMetaProperties;
    }

    /**
     * Checks whether metadata of the given meta property that applicable for a whole association exists.
     */
    public function hasRelationshipMetaProperty(string $metaPropertyName): bool
    {
        return isset($this->relationshipMetaProperties[$metaPropertyName]);
    }

    /**
     * Gets metadata of a meta property that applicable for a whole association.
     */
    public function getRelationshipMetaProperty(string $metaPropertyName): ?MetaAttributeMetadata
    {
        return $this->relationshipMetaProperties[$metaPropertyName] ?? null;
    }

    /**
     * Adds metadata of a meta property that applicable for a whole association.
     */
    public function addRelationshipMetaProperty(MetaAttributeMetadata $metaProperty): MetaAttributeMetadata
    {
        $this->relationshipMetaProperties[$metaProperty->getName()] = $metaProperty;

        return $metaProperty;
    }

    /**
     * Removes metadata of a meta property that applicable for a whole association.
     */
    public function removeRelationshipMetaProperty(string $metaPropertyName): void
    {
        unset($this->relationshipMetaProperties[$metaPropertyName]);
    }

    /**
     * Gets metadata for all links that applicable for a whole association.
     *
     * @return LinkMetadataInterface[] [link name => LinkMetadataInterface, ...]
     */
    public function getRelationshipLinks(): array
    {
        return $this->relationshipLinks;
    }

    /**
     * Checks whether metadata of the given link that applicable for a whole association exists.
     */
    public function hasRelationshipLink(string $linkName): bool
    {
        return isset($this->relationshipLinks[$linkName]);
    }

    /**
     * Gets metadata of a link that applicable for a whole association.
     */
    public function getRelationshipLink(string $linkName): ?LinkMetadataInterface
    {
        return $this->relationshipLinks[$linkName] ?? null;
    }

    /**
     * Adds metadata of a link that applicable for a whole association.
     */
    public function addRelationshipLink(string $name, LinkMetadataInterface $link): LinkMetadataInterface
    {
        $this->relationshipLinks[$name] = $link;

        return $link;
    }

    /**
     * Removes metadata of a link that applicable for a whole association.
     */
    public function removeRelationshipLink(string $linkName): void
    {
        unset($this->relationshipLinks[$linkName]);
    }
}
