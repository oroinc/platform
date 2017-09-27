<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

interface AttributeTypeInterface extends AttributeConfigurationInterface, AttributeValueInterface
{
    /**
     * @return string
     */
    public function getType();
}
