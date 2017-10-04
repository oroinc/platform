<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

interface AttributeConfigurationInterface
{
    /**
     * Will be stored in all_text data
     *
     * @param FieldConfigModel|null $attribute
     *
     * @return bool
     */
    public function isSearchable(FieldConfigModel $attribute = null);

    /**
     * @param FieldConfigModel|null $attribute
     *
     * @return bool
     */
    public function isFilterable(FieldConfigModel $attribute = null);

    /**
     * @param FieldConfigModel|null $attribute
     *
     * @return bool
     */
    public function isSortable(FieldConfigModel $attribute = null);
}
