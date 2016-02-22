<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class FilterFieldConfig implements FieldConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\FieldConfigTrait;
    use Traits\DescriptionTrait;

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
        $this->removeItemWithDefaultValue($result, ConfigUtil::EXCLUDE);
        $this->removeItemWithDefaultValue($result, ConfigUtil::ALLOW_ARRAY);

        return $result;
    }

    /**
     * Indicates whether the data type is set.
     *
     * @return bool
     */
    public function hasDataType()
    {
        return array_key_exists(ConfigUtil::DATA_TYPE, $this->items);
    }

    /**
     * Gets expected data type of the filter value.
     *
     * @return string|null
     */
    public function getDataType()
    {
        return array_key_exists(ConfigUtil::DATA_TYPE, $this->items)
            ? $this->items[ConfigUtil::DATA_TYPE]
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
            $this->items[ConfigUtil::DATA_TYPE] = $dataType;
        } else {
            unset($this->items[ConfigUtil::DATA_TYPE]);
        }
    }

    /**
     * Indicates whether the "array allowed" flag is set explicitly.
     *
     * @return bool
     */
    public function hasArrayAllowed()
    {
        return array_key_exists(ConfigUtil::ALLOW_ARRAY, $this->items);
    }

    /**
     * Indicates whether the filter value can be an array.
     *
     * @return bool
     */
    public function isArrayAllowed()
    {
        return array_key_exists(ConfigUtil::ALLOW_ARRAY, $this->items)
            ? $this->items[ConfigUtil::ALLOW_ARRAY]
            : false;
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
     * Gets the default value the filter.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return array_key_exists(ConfigUtil::DEFAULT_VALUE, $this->items)
            ? $this->items[ConfigUtil::DEFAULT_VALUE]
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
            $this->items[ConfigUtil::DEFAULT_VALUE] = $defaultValue;
        } else {
            unset($this->items[ConfigUtil::DEFAULT_VALUE]);
        }
    }
}
