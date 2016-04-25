<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Component\EntitySerializer\FieldConfig;

/**
 * Represents a filter configuration for a field.
 */
class FilterFieldConfig implements FieldConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\FieldConfigTrait;
    use Traits\DescriptionTrait;

    /** a flag indicates whether the field should be excluded */
    const EXCLUDE = FieldConfig::EXCLUDE;

    /** the path of the field value */
    const PROPERTY_PATH = FieldConfig::PROPERTY_PATH;

    /** the data type of the filter value */
    const DATA_TYPE = 'data_type';

    /** a flag indicates whether the filter value can be an array */
    const ALLOW_ARRAY = 'allow_array';

    /** the default value for the filter */
    const DEFAULT_VALUE = 'default_value';

    /** a human-readable description of the filter */
    const DESCRIPTION = EntityDefinitionFieldConfig::DESCRIPTION;

    /** @var array */
    protected $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->items;
        $this->removeItemWithDefaultValue($result, self::EXCLUDE);
        $this->removeItemWithDefaultValue($result, self::ALLOW_ARRAY);

        return $result;
    }

    /**
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->items = array_map(
            function ($value) {
                return is_object($value) ? clone $value : $value;
            },
            $this->items
        );
    }

    /**
     * Indicates whether the data type is set.
     *
     * @return bool
     */
    public function hasDataType()
    {
        return array_key_exists(self::DATA_TYPE, $this->items);
    }

    /**
     * Gets expected data type of the filter value.
     *
     * @return string|null
     */
    public function getDataType()
    {
        return array_key_exists(self::DATA_TYPE, $this->items)
            ? $this->items[self::DATA_TYPE]
            : null;
    }

    /**
     * Sets expected data type of the filter value.
     *
     * @param string|null $dataType
     */
    public function setDataType($dataType)
    {
        if ($dataType) {
            $this->items[self::DATA_TYPE] = $dataType;
        } else {
            unset($this->items[self::DATA_TYPE]);
        }
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

    /**
     * Gets the default value the filter.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return array_key_exists(self::DEFAULT_VALUE, $this->items)
            ? $this->items[self::DEFAULT_VALUE]
            : null;
    }

    /**
     * Sets the default value the filter.
     *
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        if (null !== $defaultValue) {
            $this->items[self::DEFAULT_VALUE] = $defaultValue;
        } else {
            unset($this->items[self::DEFAULT_VALUE]);
        }
    }
}
