<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of all fields that can be used to filter data.
 */
class FiltersConfig implements EntityConfigInterface
{
    /** @var string|null */
    protected $exclusionPolicy;

    /** @var array */
    protected $items = [];

    /** @var FilterFieldConfig[] */
    protected $fields = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = ConfigUtil::convertItemsToArray($this->items);
        if (null !== $this->exclusionPolicy && ConfigUtil::EXCLUSION_POLICY_NONE !== $this->exclusionPolicy) {
            $result[ConfigUtil::EXCLUSION_POLICY] = $this->exclusionPolicy;
        }
        $fields = ConfigUtil::convertObjectsToArray($this->fields, true);
        if ($fields) {
            $result[ConfigUtil::FIELDS] = $fields;
        }

        return $result;
    }

    /**
     * Indicates whether the entity does not have a configuration.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return
            null === $this->exclusionPolicy
            && empty($this->items)
            && empty($this->fields);
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
        $this->fields = ConfigUtil::cloneObjects($this->fields);
    }

    /**
     * Indicates whether the configuration of at least one filter exists.
     *
     * @return bool
     */
    public function hasFields()
    {
        return !empty($this->fields);
    }

    /**
     * Gets the configuration for all filters.
     *
     * @return FilterFieldConfig[] [field name => config, ...]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Indicates whether the configuration of the filter exists.
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
     * Gets the configuration of the filter.
     *
     * @param string $fieldName
     *
     * @return FilterFieldConfig|null
     */
    public function getField($fieldName)
    {
        if (!isset($this->fields[$fieldName])) {
            return null;
        }

        return $this->fields[$fieldName];
    }

    /**
     * Finds the configuration of the filter by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     *
     * @param string $fieldName
     * @param bool   $findByPropertyPath
     *
     * @return FilterFieldConfig|null
     */
    public function findField($fieldName, $findByPropertyPath = false)
    {
        return FindFieldUtil::doFindField($this->fields, $fieldName, $findByPropertyPath);
    }

    /**
     * Finds the name of the filter by its property path.
     *
     * @param string $propertyPath
     *
     * @return string|null
     */
    public function findFieldNameByPropertyPath($propertyPath)
    {
        return FindFieldUtil::doFindFieldNameByPropertyPath($this->fields, $propertyPath);
    }

    /**
     * Gets the configuration of existing filter or adds new filter for a given field.
     *
     * @param string $fieldName
     *
     * @return FilterFieldConfig
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
     * Adds the configuration of the filter.
     *
     * @param string                 $fieldName
     * @param FilterFieldConfig|null $field
     *
     * @return FilterFieldConfig
     */
    public function addField($fieldName, $field = null)
    {
        if (null === $field) {
            $field = new FilterFieldConfig();
        }

        $this->fields[$fieldName] = $field;

        return $field;
    }

    /**
     * Removes the configuration of the filter.
     *
     * @param string $fieldName
     */
    public function removeField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $defaultValue = null)
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if (null !== $value) {
            $this->items[$key] = $value;
        } else {
            unset($this->items[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return \array_keys($this->items);
    }

    /**
     * Indicates whether the exclusion policy is set explicitly.
     *
     * @return bool
     */
    public function hasExclusionPolicy()
    {
        return null !== $this->exclusionPolicy;
    }

    /**
     * Gets the exclusion strategy that should be used for the entity.
     *
     * @return string An exclusion strategy, e.g. "none" or "all"
     */
    public function getExclusionPolicy()
    {
        if (null === $this->exclusionPolicy) {
            return ConfigUtil::EXCLUSION_POLICY_NONE;
        }

        return $this->exclusionPolicy;
    }

    /**
     * Sets the exclusion strategy that should be used for the entity.
     *
     * @param string|null $exclusionPolicy An exclusion strategy, e.g. "none" or "all",
     *                                     or NULL to remove this option
     */
    public function setExclusionPolicy($exclusionPolicy)
    {
        $this->exclusionPolicy = $exclusionPolicy;
    }

    /**
     * Indicates whether all fields are not configured explicitly should be excluded.
     *
     * @return bool
     */
    public function isExcludeAll()
    {
        return ConfigUtil::EXCLUSION_POLICY_ALL === $this->exclusionPolicy;
    }

    /**
     * Sets the exclusion strategy to exclude all fields are not configured explicitly.
     */
    public function setExcludeAll()
    {
        $this->exclusionPolicy = ConfigUtil::EXCLUSION_POLICY_ALL;
    }

    /**
     * Sets the exclusion strategy to exclude only fields are marked as excluded.
     */
    public function setExcludeNone()
    {
        $this->exclusionPolicy = ConfigUtil::EXCLUSION_POLICY_NONE;
    }
}
