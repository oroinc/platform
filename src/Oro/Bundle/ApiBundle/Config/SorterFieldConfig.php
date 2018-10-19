<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of a field that can be used to sort data.
 */
class SorterFieldConfig implements FieldConfigInterface
{
    /** @var bool|null */
    protected $exclude;

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
}
