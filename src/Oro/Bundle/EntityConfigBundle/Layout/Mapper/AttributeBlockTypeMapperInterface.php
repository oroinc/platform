<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

interface AttributeBlockTypeMapperInterface
{
    /**
     * Return block type name or null if block type not supported by mapper
     * @param FieldConfigModel $attribute
     *
     * @return string|null
     */
    public function getBlockType(FieldConfigModel $attribute);
}
