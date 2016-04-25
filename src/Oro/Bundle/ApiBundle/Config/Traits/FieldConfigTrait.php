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
     * @return string|null
     */
    public function getPropertyPath()
    {
        return array_key_exists(FieldConfig::PROPERTY_PATH, $this->items)
            ? $this->items[FieldConfig::PROPERTY_PATH]
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
            $this->items[FieldConfig::PROPERTY_PATH] = $propertyPath;
        } else {
            unset($this->items[FieldConfig::PROPERTY_PATH]);
        }
    }
}
