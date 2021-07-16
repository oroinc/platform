<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;

interface AttributeValueProviderInterface
{
    public function removeAttributeValues(
        AttributeFamily $attributeFamily,
        array $names
    );
}
