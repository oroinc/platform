<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Component\EntitySerializer\FieldConfig;

/**
 * @property array $items
 */
trait FieldConfigTrait
{
    use ExcludeTrait;

    /**
     * Indicates whether the path of the field value exists.
     *
     * @return string
     */
    public function hasPropertyPath()
    {
        return array_key_exists(FieldConfig::PROPERTY_PATH, $this->items);
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
        return !empty($this->items[FieldConfig::PROPERTY_PATH])
            ? $this->items[FieldConfig::PROPERTY_PATH]
            : $defaultValue;
    }

    /**
     * Sets the path of the field value.
     *
     * @param string|null $propertyPath
     */
    public function setPropertyPath($propertyPath = null)
    {
        if ($propertyPath) {
            $this->items[FieldConfig::PROPERTY_PATH] = $propertyPath;
        } else {
            unset($this->items[FieldConfig::PROPERTY_PATH]);
        }
    }
}
