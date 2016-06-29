<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Traits;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ActionConfig
{
    use Traits\ConfigTrait;
    use Traits\ExcludeTrait;
    use Traits\AclResourceTrait;
    use Traits\DescriptionTrait;
    use Traits\MaxResultsTrait;
    use Traits\StatusCodesTrait;
    use Traits\FormTrait;

    /** a flag indicates whether the action should not be available for the entity */
    const EXCLUDE = ConfigUtil::EXCLUDE;

    /** the name of ACL resource */
    const ACL_RESOURCE = EntityDefinitionConfig::ACL_RESOURCE;

    /** the entity description for the action  */
    const DESCRIPTION = EntityDefinitionConfig::DESCRIPTION;

    /** the maximum number of items in the result */
    const MAX_RESULTS = EntityDefinitionConfig::MAX_RESULTS;

    /** the maximum number of items in the result */
    const STATUS_CODES = EntityDefinitionConfig::STATUS_CODES;

    /** the form type that should be used for the entity */
    const FORM_TYPE = 'form_type';

    /** the form options that should be used for the entity */
    const FORM_OPTIONS = 'form_options';

    /** a list of fields */
    const FIELDS = EntityDefinitionConfig::FIELDS;

    /** @var array */
    protected $items = [];

    /** @var ActionFieldConfig[] */
    protected $fields = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->items;

        $keys = array_keys($result);
        foreach ($keys as $key) {
            $value = $result[$key];
            if (is_object($value) && method_exists($value, 'toArray')) {
                $result[$key] = $value->toArray();
            }
        }
        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldName => $field) {
                $fieldConfig                      = $field->toArray();
                $result[self::FIELDS][$fieldName] = !empty($fieldConfig) ? $fieldConfig : null;
            }
        }

        return $result;
    }

    /**
     * Indicates whether the action does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return
            empty($this->items)
            && empty($this->fields);
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
        $this->fields = array_map(
            function ($field) {
                return clone $field;
            },
            $this->fields
        );
    }

    /**
     * Checks whether the configuration of at least one field exists.
     *
     * @return bool
     */
    public function hasFields()
    {
        return !empty($this->fields);
    }

    /**
     * Gets the configuration for all fields.
     *
     * @return ActionFieldConfig[] [field name => config, ...]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Checks whether the configuration of the field exists.
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Gets the configuration of the field.
     *
     * @param string $fieldName
     *
     * @return ActionFieldConfig|null
     */
    public function getField($fieldName)
    {
        return isset($this->fields[$fieldName])
            ? $this->fields[$fieldName]
            : null;
    }

    /**
     * Gets the configuration of existing field or adds new field.
     *
     * @param string $fieldName
     *
     * @return ActionFieldConfig
     */
    public function getOrAddField($fieldName)
    {
        $field = $this->getField($fieldName);
        if (null === $field) {
            $field = $this->addField($fieldName);
        }

        return $field;
    }

    /**
     * Adds the configuration of the field.
     *
     * @param string                 $fieldName
     * @param ActionFieldConfig|null $field
     *
     * @return ActionFieldConfig
     */
    public function addField($fieldName, $field = null)
    {
        if (null === $field) {
            $field = new ActionFieldConfig();
        }

        $this->fields[$fieldName] = $field;

        return $field;
    }

    /**
     * Removes the configuration of the field.
     *
     * @param string $fieldName
     */
    public function removeField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }
}
