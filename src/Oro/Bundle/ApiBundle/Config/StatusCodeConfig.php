<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * Represents the response status code.
 */
class StatusCodeConfig
{
    use Traits\ConfigTrait;
    use Traits\ExcludeTrait;
    use Traits\DescriptionTrait;

    /** a flag indicates whether the status code should be excluded */
    const EXCLUDE = EntityDefinitionFieldConfig::EXCLUDE;

    /** a human-readable description of the status code */
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
}
