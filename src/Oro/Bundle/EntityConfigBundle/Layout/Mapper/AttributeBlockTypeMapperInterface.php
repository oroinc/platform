<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

interface AttributeBlockTypeMapperInterface
{
    /**
     * @param FieldConfigModel $attribute
     *
     * @return string|null
     */
    public function getBlockType(FieldConfigModel $attribute);
}
