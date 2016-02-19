<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @property array $items
 */
trait FieldConfigTrait
{
    /**
     * Indicates whether the exclusion flag is set explicitly.
     *
     * @return bool
     */
    public function hasExcluded()
    {
        return array_key_exists(ConfigUtil::EXCLUDE, $this->items);
    }

    /**
     * Indicates whether the field should be excluded.
     *
     * @return bool
     */
    public function isExcluded()
    {
        return array_key_exists(ConfigUtil::EXCLUDE, $this->items)
            ? $this->items[ConfigUtil::EXCLUDE]
            : false;
    }

    /**
     * Sets a flag indicates whether the field should be excluded.
     *
     * @param bool $exclude
     */
    public function setExcluded($exclude = true)
    {
        $this->items[ConfigUtil::EXCLUDE] = $exclude;
    }

    /**
     * Indicates whether the path of the field value exists.
     *
     * @return string
     */
    public function hasPropertyPath()
    {
        return array_key_exists(ConfigUtil::PROPERTY_PATH, $this->items);
    }

    /**
     * Gets the path of the field value.
     *
     * @return string|null
     */
    public function getPropertyPath()
    {
        return array_key_exists(ConfigUtil::PROPERTY_PATH, $this->items)
            ? $this->items[ConfigUtil::PROPERTY_PATH]
            : null;
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
