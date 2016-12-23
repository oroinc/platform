<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Entity;

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
