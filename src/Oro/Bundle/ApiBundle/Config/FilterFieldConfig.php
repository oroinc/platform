<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * Represents a filter configuration for a field.
 */
class FilterFieldConfig implements FieldConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\FieldConfigTrait;
    use Traits\DataTypeTrait;
    use Traits\DescriptionTrait;

    /** a flag indicates whether the field should be excluded */
    const EXCLUDE = EntityDefinitionFieldConfig::EXCLUDE;

    /** a human-readable description of the filter */
    const DESCRIPTION = EntityDefinitionFieldConfig::DESCRIPTION;

    /** the path of the field value */
    const PROPERTY_PATH = EntityDefinitionFieldConfig::PROPERTY_PATH;

    /** the data type of the filter value */
    const DATA_TYPE = EntityDefinitionFieldConfig::DATA_TYPE;

    /** a flag indicates whether the filter value can be an array */
    const ALLOW_ARRAY = 'allow_array';

    /** @var array */
    protected $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->convertItemsToArray();
        $this->removeItemWithDefaultValue($result, self::EXCLUDE);
        $this->removeItemWithDefaultValue($result, self::ALLOW_ARRAY);

        return $result;
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->cloneItems();
    }

    /**
     * Indicates whether the "array allowed" flag is set explicitly.
     *
     * @return bool
     */
    public function hasArrayAllowed()
    {
        return array_key_exists(self::ALLOW_ARRAY, $this->items);
    }

    /**
     * Indicates whether the filter value can be an array.
     *
     * @return bool
     */
    public function isArrayAllowed()
    {
        return array_key_exists(self::ALLOW_ARRAY, $this->items)
            ? $this->items[self::ALLOW_ARRAY]
            : false;
    }

    /**
     * Sets a flag indicates whether the filter value can be an array.
     *
     * @param bool $allowArray
     */
    public function setArrayAllowed($allowArray = true)
    {
        $this->items[self::ALLOW_ARRAY] = $allowArray;
    }
}
