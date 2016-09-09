<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;

/**
 * @property array $items
 */
trait AssociationTargetTrait
{
    /**
     * Gets the class name of a target entity.
     *
     * @return string|null
     */
    public function getTargetClass()
    {
        return array_key_exists(EntityDefinitionFieldConfig::TARGET_CLASS, $this->items)
            ? $this->items[EntityDefinitionFieldConfig::TARGET_CLASS]
            : null;
    }

    /**
     * Sets the class name of a target entity.
     *
     * @param string|null $className
     */
    public function setTargetClass($className)
    {
        if ($className) {
            $this->items[EntityDefinitionFieldConfig::TARGET_CLASS] = $className;
        } else {
            unset($this->items[EntityDefinitionFieldConfig::TARGET_CLASS]);
        }
    }

    /**
     * Indicates whether a target association represents "to-many" or "to-one" relationship.
     *
     * @return bool|null TRUE if a target association represents "to-many" relationship
     */
    public function isCollectionValuedAssociation()
    {
        return array_key_exists(EntityDefinitionFieldConfig::TARGET_TYPE, $this->items)
            ? 'to-many' === $this->items[EntityDefinitionFieldConfig::TARGET_TYPE]
            : null;
    }

    /**
     * Indicates whether the type of a target association is set explicitly.
     *
     * @return bool
     */
    public function hasTargetType()
    {
        return array_key_exists(EntityDefinitionFieldConfig::TARGET_TYPE, $this->items);
    }

    /**
     * Gets the type of a target association.
     *
     * @return string|null Can be "to-one" or "to-many"
     */
    public function getTargetType()
    {
        return array_key_exists(EntityDefinitionFieldConfig::TARGET_TYPE, $this->items)
            ? $this->items[EntityDefinitionFieldConfig::TARGET_TYPE]
            : null;
    }

    /**
     * Sets the type of a target association.
     *
     * @param string|null $targetType Can be "to-one" or "to-many"
     */
    public function setTargetType($targetType)
    {
        if ($targetType) {
            $this->items[EntityDefinitionFieldConfig::TARGET_TYPE] = $targetType;
        } else {
            unset($this->items[EntityDefinitionFieldConfig::TARGET_TYPE]);
        }
    }
}
