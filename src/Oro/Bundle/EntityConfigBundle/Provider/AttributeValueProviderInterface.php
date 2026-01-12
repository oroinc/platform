<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;

/**
 * Defines the contract for removing attribute values from an attribute family.
 *
 * Implementations of this interface handle the removal of attribute values associated with specific
 * attribute names from an attribute family, supporting cleanup operations when attributes are deleted
 * or removed from a family.
 */
interface AttributeValueProviderInterface
{
    public function removeAttributeValues(
        AttributeFamily $attributeFamily,
        array $names
    );
}
