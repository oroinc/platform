<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;

interface AttributeValueProviderInterface
{
    /**
     * @param AttributeFamily $attributeFamily
     * @param array $names
     */
    public function removeAttributeValues(
        AttributeFamily $attributeFamily,
        array $names
    );
}
