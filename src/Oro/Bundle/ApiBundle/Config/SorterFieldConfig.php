<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * Represents a sorter configuration for a field.
 */
class SorterFieldConfig implements FieldConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\FieldConfigTrait;

    /** a flag indicates whether the field should be excluded */
    const EXCLUDE = EntityDefinitionFieldConfig::EXCLUDE;

    /** the path of the field value */
    const PROPERTY_PATH = EntityDefinitionFieldConfig::PROPERTY_PATH;

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

        return $result;
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->cloneItems();
    }
}
