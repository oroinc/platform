<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of a field that can be used to filter data.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FilterFieldConfig implements FieldConfigInterface
{
    /** @var bool|null */
    protected $exclude;

    /** @var string|null */
    protected $dataType;

    /** @var array */
    protected $items = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = ConfigUtil::convertItemsToArray($this->items);
        if (true === $this->exclude) {
            $result[ConfigUtil::EXCLUDE] = $this->exclude;
        }
        if (null !== $this->dataType) {
            $result[ConfigUtil::DATA_TYPE] = $this->dataType;
        }
        if (isset($result[ConfigUtil::COLLECTION]) && false === $result[ConfigUtil::COLLECTION]) {
            unset($result[ConfigUtil::COLLECTION]);
        }
        if (isset($result[ConfigUtil::ALLOW_ARRAY]) && false === $result[ConfigUtil::ALLOW_ARRAY]) {
            unset($result[ConfigUtil::ALLOW_ARRAY]);
        }
        if (isset($result[ConfigUtil::ALLOW_RANGE]) && false === $result[ConfigUtil::ALLOW_RANGE]) {
            unset($result[ConfigUtil::ALLOW_RANGE]);
        }

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
     * {@inheritdoc}
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $defaultValue = null)
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return \array_keys($this->items);
    }

    /**
     * Indicates whether the exclusion flag is set explicitly.
     *
     * @return bool
     */
    public function hasExcluded()
    {
        return null !== $this->exclude;
    }

    /**
     * Indicates whether the exclusion flag.
     *
     * @return bool
     */
    public function isExcluded()
    {
        if (null === $this->exclude) {
            return false;
        }

        return $this->exclude;
    }

    /**
     * Sets the exclusion flag.
     *
     * @param bool|null $exclude The exclude flag or NULL to remove this option
     */
    public function setExcluded($exclude = true)
    {
        $this->exclude = $exclude;
    }

    /**
     * Indicates whether the description attribute exists.
     *
     * @return bool
     */
    public function hasDescription()
    {
        return $this->has(ConfigUtil::DESCRIPTION);
    }

    /**
     * Gets the value of the description attribute.
     *
     * @return string|Label|null
     */
    public function getDescription()
    {
        return $this->get(ConfigUtil::DESCRIPTION);
    }

    /**
     * Sets the value of the description attribute.
     *
     * @param string|Label|null $description
     */
    public function setDescription($description)
    {
        if ($description) {
            $this->items[ConfigUtil::DESCRIPTION] = $description;
        } else {
            unset($this->items[ConfigUtil::DESCRIPTION]);
        }
    }

    /**
     * Indicates whether the path of the field value exists.
     *
     * @return bool
     */
    public function hasPropertyPath()
    {
        return $this->has(ConfigUtil::PROPERTY_PATH);
    }

    /**
     * Gets the path of the field value.
     *
     * @param string|null $defaultValue
     *
     * @return string|null
     */
    public function getPropertyPath($defaultValue = null)
    {
        if (empty($this->items[ConfigUtil::PROPERTY_PATH])) {
            return $defaultValue;
        }

        return $this->items[ConfigUtil::PROPERTY_PATH];
    }

    /**
     * Sets the path of the field value.
     *
     * @param string|null $propertyPath
     */
    public function setPropertyPath($propertyPath = null)
    {
        if ($propertyPath) {
            $this->items[ConfigUtil::PROPERTY_PATH] = $propertyPath;
        } else {
            unset($this->items[ConfigUtil::PROPERTY_PATH]);
        }
    }

    /**
     * Indicates whether the "collection" option is set explicitly.
     *
     * @return bool
     */
    public function hasCollection()
    {
        return $this->has(ConfigUtil::COLLECTION);
    }

    /**
     * Indicates whether the filter represents a collection valued association.
     *
     * @return bool
     */
    public function isCollection()
    {
        return (bool)$this->get(ConfigUtil::COLLECTION);
    }

    /**
     * Sets a flag indicates whether the filter represents a collection valued association.
     *
     * @param bool $value
     */
    public function setIsCollection($value)
    {
        $this->set(ConfigUtil::COLLECTION, $value);
    }

    /**
     * Indicates whether the data type is set.
     *
     * @return bool
     */
    public function hasDataType()
    {
        return null !== $this->dataType;
    }

    /**
     * Gets expected data type of the filter value.
     *
     * @return string|null
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Sets expected data type of the filter value.
     *
     * @param string|null $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * Indicates whether the filter type is set.
     *
     * @return bool
     */
    public function hasType()
    {
        return $this->has(ConfigUtil::FILTER_TYPE);
    }

    /**
     * Gets the filter type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->get(ConfigUtil::FILTER_TYPE);
    }

    /**
     * Sets the filter type.
     *
     * @param string|null $type
     */
    public function setType($type)
    {
        if ($type) {
            $this->items[ConfigUtil::FILTER_TYPE] = $type;
        } else {
            unset($this->items[ConfigUtil::FILTER_TYPE]);
        }
    }

    /**
     * Gets the filter options.
     *
     * @return array|null
     */
    public function getOptions()
    {
        return $this->get(ConfigUtil::FILTER_OPTIONS);
    }

    /**
     * Sets the filter options.
     *
     * @param array|null $options
     */
    public function setOptions($options)
    {
        if ($options) {
            $this->items[ConfigUtil::FILTER_OPTIONS] = $options;
        } else {
            unset($this->items[ConfigUtil::FILTER_OPTIONS]);
        }
    }

    /**
     * Gets a list of operators supported by the filter.
     *
     * @return string[]|null
     */
    public function getOperators()
    {
        return $this->get(ConfigUtil::FILTER_OPERATORS);
    }

    /**
     * Sets a list of operators supported by the filter.
     *
     * @param string[]|null $operators
     */
    public function setOperators($operators)
    {
        if ($operators) {
            $this->items[ConfigUtil::FILTER_OPERATORS] = $operators;
        } else {
            unset($this->items[ConfigUtil::FILTER_OPERATORS]);
        }
    }

    /**
     * Indicates whether the "array allowed" flag is set explicitly.
     *
     * @return bool
     */
    public function hasArrayAllowed()
    {
        return $this->has(ConfigUtil::ALLOW_ARRAY);
    }

    /**
     * Indicates whether the filter value can be an array.
     *
     * @return bool
     */
    public function isArrayAllowed()
    {
        return $this->get(ConfigUtil::ALLOW_ARRAY, false);
    }

    /**
     * Sets a flag indicates whether the filter value can be an array.
     *
     * @param bool $allowArray
     */
    public function setArrayAllowed($allowArray = true)
    {
        $this->items[ConfigUtil::ALLOW_ARRAY] = $allowArray;
    }

    /**
     * Indicates whether the "range allowed" flag is set explicitly.
     *
     * @return bool
     */
    public function hasRangeAllowed()
    {
        return $this->has(ConfigUtil::ALLOW_RANGE);
    }

    /**
     * Indicates whether the filter value can be a pair of "from" and "to" values.
     *
     * @return bool
     */
    public function isRangeAllowed()
    {
        return $this->get(ConfigUtil::ALLOW_RANGE, false);
    }

    /**
     * Sets a flag indicates whether the filter value can be a pair of "from" and "to" values.
     *
     * @param bool $allowRange
     */
    public function setRangeAllowed($allowRange = true)
    {
        $this->items[ConfigUtil::ALLOW_RANGE] = $allowRange;
    }
}
