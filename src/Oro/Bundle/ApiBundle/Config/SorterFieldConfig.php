<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Component\EntitySerializer\FieldConfig;

/**
 * Represents a sorter configuration for a field.
 */
class SorterFieldConfig implements FieldConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\FieldConfigTrait;

    /** a flag indicates whether the field should be excluded */
    const EXCLUDE = FieldConfig::EXCLUDE;

    /** the path of the field value */
    const PROPERTY_PATH = FieldConfig::PROPERTY_PATH;

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

        return $result;
    }
}
