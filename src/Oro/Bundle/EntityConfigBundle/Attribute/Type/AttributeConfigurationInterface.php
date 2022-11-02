<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Attribute type parameters that define application behaviour per attribute
 * These parameters are used in many areas, e.g. to build search index and entity grids
 */
interface AttributeConfigurationInterface
{
    /**
     * Will be stored in all_text data
     *
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isSearchable(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isFilterable(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isSortable(FieldConfigModel $attribute);
}
