<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Base methods to simplify working with attribute configuration data.
 */
interface AttributeConfigurationProviderInterface
{
    /**
     * @param FieldConfigModel $attribute
     *
     * @return string
     */
    public function getAttributeLabel(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeActive(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeCustom(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeSearchable(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeFilterable(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeFilterByExactValue(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeSortable(FieldConfigModel $attribute);
}
