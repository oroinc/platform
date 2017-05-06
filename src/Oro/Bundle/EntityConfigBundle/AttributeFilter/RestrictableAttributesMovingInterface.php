<?php

namespace Oro\Bundle\EntityConfigBundle\AttributeFilter;

interface AttributesMovingFilterInterface
{
    /**
     * @param string $attributeName
     * @return bool
     */
    public function isRestrictedToMove($attributeName);
}
