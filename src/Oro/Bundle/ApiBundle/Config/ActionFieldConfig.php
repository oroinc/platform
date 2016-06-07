<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * Represents a field configuration inside "actions" section.
 */
class ActionFieldConfig implements FieldConfigInterface
{
    use Traits\ConfigTrait;
    use Traits\FieldConfigTrait;
    use Traits\FormTrait;

    /** a flag indicates whether the field should be excluded */
    const EXCLUDE = EntityDefinitionFieldConfig::EXCLUDE;

    /** the form type that should be used for the field */
    const FORM_TYPE = EntityDefinitionConfig::FORM_TYPE;

    /** the form options that should be used for the field */
    const FORM_OPTIONS = EntityDefinitionConfig::FORM_OPTIONS;

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
