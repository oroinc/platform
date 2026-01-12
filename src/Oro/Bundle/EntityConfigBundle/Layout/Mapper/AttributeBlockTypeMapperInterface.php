<?php

namespace Oro\Bundle\EntityConfigBundle\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Defines the contract for mapping attributes to layout block types.
 *
 * Implementations of this interface determine which layout block type should be used to render
 * a specific attribute in the storefront, enabling customization of attribute presentation based
 * on attribute type or target class.
 */
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
