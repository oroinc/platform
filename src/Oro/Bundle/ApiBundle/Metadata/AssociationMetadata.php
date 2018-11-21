<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ToArrayInterface;

/**
 * The metadata for an entity association.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class AssociationMetadata extends PropertyMetadata implements ToArrayInterface
{
    /** @var string */
    private $targetClass;

    /** @var string[] */
    private $acceptableTargetClasses = [];

    /** @var bool */
    private $allowEmptyAcceptableTargets = true;

    /** @var string */
    private $associationType;

    /** @var bool */
    private $collection = false;

    /** @var bool */
    private $nullable = false;

    /** @var bool */
    private $collapsed = false;

    /** @var EntityMetadata|null */
    private $targetMetadata;

    /** @var MetaAttributeMetadata[] */
    private $metaProperties = [];

    /** @var LinkMetadataInterface[] */
    private $links = [];

    /** @var MetaAttributeMetadata[] */
    private $relationshipMetaProperties = [];

    /** @var LinkMetadataInterface[] */
    private $relationshipLinks = [];

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
     * Gets a native PHP array representation of the object.
     *
     * @return array [key => value, ...]
     */
    public function toArray()
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
     * Gets metadata of the association target.
     *
     * @return EntityMetadata|null
     */
    public function getTargetMetadata()
    {
        return $this->targetMetadata;
    }

    /**
     * Sets metadata of the association target.
     *
     * @param EntityMetadata $targetMetadata
     */
    public function setTargetMetadata(EntityMetadata $targetMetadata)
    {
        $this->targetMetadata = $targetMetadata;
    }

    /**
     * Gets FQCN of the association target.
     *
     * @return string
     */
    public function getTargetClassName()
    {
        return $this->targetClass;
    }

    /**
     * Sets FQCN of the association target.
     *
     * @param string $className
     */
    public function setTargetClassName($className)
    {
        $this->targetClass = $className;
    }

    /**
     * Gets FQCN of acceptable association targets.
     *
     * @return string[]
     */
    public function getAcceptableTargetClassNames()
    {
        return $this->acceptableTargetClasses;
    }

    /**
     * Sets FQCN of acceptable association targets.
     *
     * @param string[] $classNames
     */
    public function setAcceptableTargetClassNames(array $classNames)
    {
        $this->acceptableTargetClasses = $classNames;
    }

    /**
     * Adds new acceptable association target.
     *
     * @param string $className
     */
    public function addAcceptableTargetClassName($className)
    {
        if (!in_array($className, $this->acceptableTargetClasses, true)) {
            $this->acceptableTargetClasses[] = $className;
        }
    }

    /**
     * Removes acceptable association target.
     *
     * @param string $className
     */
    public function removeAcceptableTargetClassName($className)
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
     *
     * @return bool
     */
    public function isEmptyAcceptableTargetsAllowed()
    {
        return $this->allowEmptyAcceptableTargets;
    }

    /**
     * Sets a flag indicates how to treat empty acceptable target classes.
     * TRUE means that any entity type should be accepted.
     * FALSE means that any entity type should be rejected.
     *
     * @param bool $value
     */
    public function setEmptyAcceptableTargetsAllowed($value)
    {
        $this->allowEmptyAcceptableTargets = $value;
    }

    /**
     * Gets the type of the association.
     * For example "manyToOne" or "manyToMany".
     * @see \Oro\Bundle\EntityExtendBundle\Extend\RelationType
     *
     * @return string
     */
    public function getAssociationType()
    {
        return $this->associationType;
    }

    /**
     * Sets the type of the association.
     * For example "manyToOne" or "manyToMany".
     * @see \Oro\Bundle\EntityExtendBundle\Extend\RelationType
     *
     * @param string $associationType
     */
    public function setAssociationType($associationType)
    {
        $this->associationType = $associationType;
    }

    /**
     * Whether the association represents "to-many" or "to-one" relationship.
     *
     * @return bool
     */
    public function isCollection()
    {
        return $this->collection;
    }

    /**
     * Sets a flag indicates whether the association represents "to-many" or "to-one" relationship.
     *
     * @param bool $value TRUE for "to-many" relation, FALSE for "to-one" relationship
     */
    public function setIsCollection($value)
    {
        $this->collection = $value;
    }

    /**
     * Whether a value of the association can be NULL.
     *
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * Sets a flag indicates whether a value of the association can be NULL.
     *
     * @param bool $value
     */
    public function setIsNullable($value)
    {
        $this->nullable = $value;
    }

    /**
     * Indicates whether the association should be collapsed to a scalar.
     *
     * @return bool
     */
    public function isCollapsed()
    {
        return $this->collapsed;
    }

    /**
     * Sets a flag indicates whether the association should be collapsed to a scalar.
     *
     * @param bool $collapsed
     */
    public function setCollapsed($collapsed = true)
    {
        $this->collapsed = $collapsed;
    }

    /**
     * Gets metadata for all meta properties that applicable
     * for the association value or each value of collection valued association.
     *
     * @return MetaAttributeMetadata[] [meta property name => MetaAttributeMetadata, ...]
     */
    public function getMetaProperties()
    {
        return $this->metaProperties;
    }

    /**
     * Checks whether metadata of the given meta property that applicable
     * for the association value or each value of collection valued association exists.
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
     * Gets metadata of a meta property that applicable
     * for the association value or each value of collection valued association.
     *
     * @param string $metaPropertyName
     *
     * @return MetaAttributeMetadata|null
     */
    public function getMetaProperty($metaPropertyName)
    {
        if (!isset($this->metaProperties[$metaPropertyName])) {
            return null;
        }

        return $this->metaProperties[$metaPropertyName];
    }

    /**
     * Adds metadata of a meta property that applicable
     * for the association value or each value of collection valued association.
     *
     * @param MetaAttributeMetadata $metaProperty
     *
     * @return MetaAttributeMetadata
     */
    public function addMetaProperty(MetaAttributeMetadata $metaProperty)
    {
        $this->metaProperties[$metaProperty->getName()] = $metaProperty;

        return $metaProperty;
    }

    /**
     * Removes metadata of a meta property that applicable
     * for the association value or each value of collection valued association.
     *
     * @param string $metaPropertyName
     */
    public function removeMetaProperty($metaPropertyName)
    {
        unset($this->metaProperties[$metaPropertyName]);
    }

    /**
     * Gets metadata for all links that applicable
     * for the association value or each value of collection valued association.
     *
     * @return LinkMetadataInterface[] [link name => LinkMetadataInterface, ...]
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Checks whether metadata of the given link that applicable
     * for the association value or each value of collection valued association exists.
     *
     * @param string $linkName
     *
     * @return bool
     */
    public function hasLink($linkName)
    {
        return isset($this->links[$linkName]);
    }

    /**
     * Gets metadata of a link that applicable
     * for the association value or each value of collection valued association.
     *
     * @param string $linkName
     *
     * @return LinkMetadataInterface|null
     */
    public function getLink($linkName)
    {
        if (!isset($this->links[$linkName])) {
            return null;
        }

        return $this->links[$linkName];
    }

    /**
     * Adds metadata of a link that applicable
     * for the association value or each value of collection valued association.
     *
     * @param string                $name
     * @param LinkMetadataInterface $link
     *
     * @return LinkMetadataInterface
     */
    public function addLink(string $name, LinkMetadataInterface $link)
    {
        $this->links[$name] = $link;

        return $link;
    }

    /**
     * Removes metadata of a link that applicable
     * for the association value or each value of collection valued association.
     *
     * @param string $linkName
     */
    public function removeLink($linkName)
    {
        unset($this->links[$linkName]);
    }

    /**
     * Gets metadata for all meta properties that applicable for a whole association.
     *
     * @return MetaAttributeMetadata[] [meta property name => MetaAttributeMetadata, ...]
     */
    public function getRelationshipMetaProperties()
    {
        return $this->relationshipMetaProperties;
    }

    /**
     * Checks whether metadata of the given meta property that applicable for a whole association exists.
     *
     * @param string $metaPropertyName
     *
     * @return bool
     */
    public function hasRelationshipMetaProperty($metaPropertyName)
    {
        return isset($this->relationshipMetaProperties[$metaPropertyName]);
    }

    /**
     * Gets metadata of a meta property that applicable for a whole association.
     *
     * @param string $metaPropertyName
     *
     * @return MetaAttributeMetadata|null
     */
    public function getRelationshipMetaProperty($metaPropertyName)
    {
        if (!isset($this->relationshipMetaProperties[$metaPropertyName])) {
            return null;
        }

        return $this->relationshipMetaProperties[$metaPropertyName];
    }

    /**
     * Adds metadata of a meta property that applicable for a whole association.
     *
     * @param MetaAttributeMetadata $metaProperty
     *
     * @return MetaAttributeMetadata
     */
    public function addRelationshipMetaProperty(MetaAttributeMetadata $metaProperty)
    {
        $this->relationshipMetaProperties[$metaProperty->getName()] = $metaProperty;

        return $metaProperty;
    }

    /**
     * Removes metadata of a meta property that applicable for a whole association.
     *
     * @param string $metaPropertyName
     */
    public function removeRelationshipMetaProperty($metaPropertyName)
    {
        unset($this->relationshipMetaProperties[$metaPropertyName]);
    }

    /**
     * Gets metadata for all links that applicable for a whole association.
     *
     * @return LinkMetadataInterface[] [link name => LinkMetadataInterface, ...]
     */
    public function getRelationshipLinks()
    {
        return $this->relationshipLinks;
    }

    /**
     * Checks whether metadata of the given link that applicable for a whole association exists.
     *
     * @param string $linkName
     *
     * @return bool
     */
    public function hasRelationshipLink($linkName)
    {
        return isset($this->relationshipLinks[$linkName]);
    }

    /**
     * Gets metadata of a link that applicable for a whole association.
     *
     * @param string $linkName
     *
     * @return LinkMetadataInterface|null
     */
    public function getRelationshipLink($linkName)
    {
        if (!isset($this->relationshipLinks[$linkName])) {
            return null;
        }

        return $this->relationshipLinks[$linkName];
    }

    /**
     * Adds metadata of a link that applicable for a whole association.
     *
     * @param string                $name
     * @param LinkMetadataInterface $link
     *
     * @return LinkMetadataInterface
     */
    public function addRelationshipLink(string $name, LinkMetadataInterface $link)
    {
        $this->relationshipLinks[$name] = $link;

        return $link;
    }

    /**
     * Removes metadata of a link that applicable for a whole association.
     *
     * @param string $linkName
     */
    public function removeRelationshipLink($linkName)
    {
        unset($this->relationshipLinks[$linkName]);
    }
}
