<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Traits;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ActionConfig
{
    use Traits\ConfigTrait;
    use Traits\ExcludeTrait;
    use Traits\DescriptionTrait;
    use Traits\DocumentationTrait;
    use Traits\AclResourceTrait;
    use Traits\MaxResultsTrait;
    use Traits\PageSizeTrait;
    use Traits\SortingTrait;
    use Traits\InclusionTrait;
    use Traits\FieldsetTrait;
    use Traits\FormTrait;
    use Traits\StatusCodesTrait;

    /** a flag indicates whether the action should not be available for the entity */
    const EXCLUDE = ConfigUtil::EXCLUDE;

    /** a short, human-readable description of API resource */
    const DESCRIPTION = EntityDefinitionConfig::DESCRIPTION;

    /** a detailed documentation of API resource */
    const DOCUMENTATION = EntityDefinitionConfig::DOCUMENTATION;

    /** the name of ACL resource that should be used to protect the entity */
    const ACL_RESOURCE = EntityDefinitionConfig::ACL_RESOURCE;

    /** the maximum number of items in the result */
    const MAX_RESULTS = EntityDefinitionConfig::MAX_RESULTS;

    /** the default page size */
    const PAGE_SIZE = EntityDefinitionConfig::PAGE_SIZE;

    /** the default ordering of the result */
    const ORDER_BY = EntityDefinitionConfig::ORDER_BY;

    /** a flag indicates whether a sorting is disabled */
    const DISABLE_SORTING = EntityDefinitionConfig::DISABLE_SORTING;

    /** a flag indicates whether an inclusion of related entities is disabled */
    const DISABLE_INCLUSION = EntityDefinitionConfig::DISABLE_INCLUSION;

    /** a flag indicates whether a requesting of a restricted set of fields is disabled */
    const DISABLE_FIELDSET = EntityDefinitionConfig::DISABLE_FIELDSET;

    /** the form type that should be used for the entity */
    const FORM_TYPE = EntityDefinitionConfig::FORM_TYPE;

    /** the form options that should be used for the entity */
    const FORM_OPTIONS = EntityDefinitionConfig::FORM_OPTIONS;

    /** additional response status codes for the entity */
    const STATUS_CODES = EntityDefinitionConfig::STATUS_CODES;

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
        $result = $this->convertItemsToArray();
        $fields = ConfigUtil::convertObjectsToArray($this->fields, true);
        if (!empty($fields)) {
            $result[self::FIELDS] = $fields;
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
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->cloneItems();
        $this->fields = ConfigUtil::cloneObjects($this->fields);
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

    /**
     * Gets the default ordering of the result.
     * The direction can be "ASC" or "DESC".
     * The Doctrine\Common\Collections\Criteria::ASC and Doctrine\Common\Collections\Criteria::DESC constants
     * can be used.
     *
     * @return array [field name => direction, ...]
     */
    public function getOrderBy()
    {
        return array_key_exists(self::ORDER_BY, $this->items)
            ? $this->items[self::ORDER_BY]
            : [];
    }

    /**
     * Sets the default ordering of the result.
     * The direction can be "ASC" or "DESC".
     * The Doctrine\Common\Collections\Criteria::ASC and Doctrine\Common\Collections\Criteria::DESC constants
     * can be used.
     *
     * @param array $orderBy [field name => direction, ...]
     */
    public function setOrderBy(array $orderBy = [])
    {
        if (!empty($orderBy)) {
            $this->items[self::ORDER_BY] = $orderBy;
        } else {
            unset($this->items[self::ORDER_BY]);
        }
    }
}
