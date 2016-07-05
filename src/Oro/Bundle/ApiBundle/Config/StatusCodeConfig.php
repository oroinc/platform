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
