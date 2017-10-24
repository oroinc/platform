<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

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

    /** the type of the filter */
    const TYPE = 'type';

    /** the filter options  */
    const OPTIONS = 'options';

    /** a list of operators supported by the filter */
    const OPERATORS = 'operators';

    /** the data type of the filter value */
    const DATA_TYPE = EntityDefinitionFieldConfig::DATA_TYPE;

    /** a flag indicates whether the filter value can be an array */
    const ALLOW_ARRAY = 'allow_array';

    /** a flag indicates whether the filter value can be a pair of "from" and "to" values */
    const ALLOW_RANGE = 'allow_range';

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
        $this->removeItemWithDefaultValue($result, self::ALLOW_RANGE);

        return $result;
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
    }

    /**
     * Indicates whether the filter type is set.
     *
     * @return bool
     */
    public function hasType()
    {
        return array_key_exists(self::TYPE, $this->items);
    }

    /**
     * Gets the filter type.
     *
     * @return string|null
     */
    public function getType()
    {
        if (!array_key_exists(self::TYPE, $this->items)) {
            return null;
        }

        return $this->items[self::TYPE];
    }

    /**
     * Sets the filter type.
     *
     * @param string|null $type
     */
    public function setType($type)
    {
        if ($type) {
            $this->items[self::TYPE] = $type;
        } else {
            unset($this->items[self::TYPE]);
        }
    }

    /**
     * Gets the filter options.
     *
     * @return array|null
     */
    public function getOptions()
    {
        if (!array_key_exists(self::OPTIONS, $this->items)) {
            return null;
        }

        return $this->items[self::OPTIONS];
    }

    /**
     * Sets the filter options.
     *
     * @param array|null $options
     */
    public function setOptions($options)
    {
        if ($options) {
            $this->items[self::OPTIONS] = $options;
        } else {
            unset($this->items[self::OPTIONS]);
        }
    }

    /**
     * Gets a list of operators supported by the filter.
     *
     * @return string[]|null
     */
    public function getOperators()
    {
        if (!array_key_exists(self::OPERATORS, $this->items)) {
            return null;
        }

        return $this->items[self::OPERATORS];
    }

    /**
     * Sets a list of operators supported by the filter.
     *
     * @param string[]|null $operators
     */
    public function setOperators($operators)
    {
        if ($operators) {
            $this->items[self::OPERATORS] = $operators;
        } else {
            unset($this->items[self::OPERATORS]);
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
        if (!array_key_exists(self::ALLOW_ARRAY, $this->items)) {
            return false;
        }

        return $this->items[self::ALLOW_ARRAY];
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
     * Indicates whether the "range allowed" flag is set explicitly.
     *
     * @return bool
     */
    public function hasRangeAllowed()
    {
        return array_key_exists(self::ALLOW_RANGE, $this->items);
    }

    /**
     * Indicates whether the filter value can be a pair of "from" and "to" values.
     *
     * @return bool
     */
    public function isRangeAllowed()
    {
        if (!array_key_exists(self::ALLOW_RANGE, $this->items)) {
            return false;
        }

        return $this->items[self::ALLOW_RANGE];
    }

    /**
     * Sets a flag indicates whether the filter value can be a pair of "from" and "to" values.
     *
     * @param bool $allowRange
     */
    public function setRangeAllowed($allowRange = true)
    {
        $this->items[self::ALLOW_RANGE] = $allowRange;
    }
}
