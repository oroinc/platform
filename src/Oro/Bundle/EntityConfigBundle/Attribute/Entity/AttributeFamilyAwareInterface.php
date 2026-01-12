<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Entity;

/**
 * Defines the contract for entities that can be associated with an attribute family.
 *
 * Entities implementing this interface can be configured to use a specific attribute family, which determines
 * which attributes are available and how they are organized. This allows for flexible attribute management
 * across different entity types.
 */
interface AttributeFamilyAwareInterface
{
    /**
     * @return AttributeFamily
     */
    public function getAttributeFamily();

    /**
     * @param AttributeFamily $attributeFamily
     * @return mixed
     */
    public function setAttributeFamily(AttributeFamily $attributeFamily);
}
