<?php

namespace Oro\Component\EntitySerializer;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityConfig
{
    /** a list of fields */
    const FIELDS = 'fields';

    /** a type of the exclusion strategy that should be used for the entity */
    const EXCLUSION_POLICY = 'exclusion_policy';

    /** exclude all fields are not configured explicitly */
    const EXCLUSION_POLICY_ALL = 'all';

    /** exclude only fields are marked as excluded */
    const EXCLUSION_POLICY_NONE = 'none';

    /** a flag indicates whether using of Doctrine partial object is disabled */
    const DISABLE_PARTIAL_LOAD = 'disable_partial_load';

    /** the ordering of the result */
    const ORDER_BY = 'order_by';

    /** the maximum number of items in the result */
    const MAX_RESULTS = 'max_results';

    /** a list Doctrine query hints */
    const HINTS = 'hints';

    /** a handler that can be used to modify serialized data */
    const POST_SERIALIZE = 'post_serialize';

    /** @var array */
    protected $items = [];

    /** @var FieldConfig[] */
    protected $fields = [];

    /**
     * Gets a native PHP array representation of the entity configuration.
     *
     * @return array
     */
    public function toArray()
    {
        $result = $this->items;

        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldName => $fieldConfig) {
                $result[self::FIELDS][$fieldName] = $fieldConfig->toArray();
            }
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
     * Checks whether the configuration attribute exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Gets the configuration value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return array_key_exists($key, $this->items)
            ? $this->items[$key]
            : null;
    }

    /**
     * Sets the configuration value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * Removes the configuration value.
     *
     * @param string $key
     */
    public function remove($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Gets the configuration for all fields.
     *
     * @return FieldConfig[] [field name => config, ...]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Checks whether the configuration of a field exists.
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
     * Gets the configuration of a field.
     *
     * @param string $fieldName
     *
     * @return FieldConfig|null
     */
    public function getField($fieldName)
    {
        return isset($this->fields[$fieldName])
            ? $this->fields[$fieldName]
            : null;
    }

    /**
     * Adds the configuration of a field.
     *
     * @param string           $fieldName
     * @param FieldConfig|null $field
     *
     * @return FieldConfig
     */
    public function addField($fieldName, $field = null)
    {
        if (null === $field) {
            $field = new FieldConfig();
        }

        $this->fields[$fieldName] = $field;

        return $field;
    }

    /**
     * Removes the configuration of a field.
     *
     * @param string $fieldName
     */
    public function removeField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * Gets the exclusion strategy that should be used for the entity.
     *
     * @return string One of EXCLUSION_POLICY_* constant
     */
    public function getExclusionPolicy()
    {
        return array_key_exists(self::EXCLUSION_POLICY, $this->items)
            ? $this->items[self::EXCLUSION_POLICY]
            : self::EXCLUSION_POLICY_NONE;
    }

    /**
     * Sets the exclusion strategy that should be used for the entity.
     *
     * @param string $exclusionPolicy One of EXCLUSION_POLICY_* constant
     */
    public function setExclusionPolicy($exclusionPolicy)
    {
        if ($exclusionPolicy && self::EXCLUSION_POLICY_NONE !== $exclusionPolicy) {
            $this->items[self::EXCLUSION_POLICY] = $exclusionPolicy;
        } else {
            unset($this->items[self::EXCLUSION_POLICY]);
        }
    }

    /**
     * Indicates whether all fields are not configured explicitly should be excluded.
     *
     * @return bool
     */
    public function isExcludeAll()
    {
        return self::EXCLUSION_POLICY_ALL === $this->getExclusionPolicy();
    }

    /**
     * Sets the exclusion strategy to exclude all fields are not configured explicitly.
     */
    public function setExcludeAll()
    {
        $this->setExclusionPolicy(self::EXCLUSION_POLICY_ALL);
    }

    /**
     * Sets the exclusion strategy to exclude only fields are marked as excluded.
     */
    public function setExcludeNone()
    {
        $this->setExclusionPolicy(self::EXCLUSION_POLICY_NONE);
    }

    /**
     * Indicates whether using of Doctrine partial object is enabled.
     *
     * @return bool
     */
    public function isPartialLoadEnabled()
    {
        return array_key_exists(self::DISABLE_PARTIAL_LOAD, $this->items)
            ? !$this->items[self::DISABLE_PARTIAL_LOAD]
            : true;
    }

    /**
     * Allows using of Doctrine partial object.
     */
    public function enablePartialLoad()
    {
        unset($this->items[self::DISABLE_PARTIAL_LOAD]);
    }

    /**
     * Prohibits using of Doctrine partial object.
     */
    public function disablePartialLoad()
    {
        $this->items[self::DISABLE_PARTIAL_LOAD] = true;
    }

    /**
     * Gets the ordering of the result.
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
     * Sets the ordering of the result.
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

    /**
     * Gets the maximum number of items in the result.
     *
     * @return int|null The requested maximum number of items or NULL if not limited
     */
    public function getMaxResults()
    {
        return array_key_exists(self::MAX_RESULTS, $this->items)
            ? $this->items[self::MAX_RESULTS]
            : null;
    }

    /**
     * Sets the maximum number of items in the result.
     *
     * @param int|null $maxResults The maximum number of items or NULL to set unlimited
     */
    public function setMaxResults($maxResults = null)
    {
        if (null === $maxResults || $maxResults < 0) {
            unset($this->items[self::MAX_RESULTS]);
        } else {
            $this->items[self::MAX_RESULTS] = $maxResults;
        }
    }

    /**
     * Gets Doctrine query hints.
     * Each hint can be a string or an associative array with "name" and "value" keys.
     *
     * @return array
     */
    public function getHints()
    {
        return array_key_exists(self::HINTS, $this->items)
            ? $this->items[self::HINTS]
            : [];
    }

    /**
     * Adds Doctrine query hint.
     *
     * @param string $name  The name of the hint
     * @param mixed  $value The value of the hint
     */
    public function addHint($name, $value = null)
    {
        $hints   = $this->getHints();
        $hints[] = null === $value
            ? $name
            : ['name' => $name, 'value' => $value];

        $this->items[self::HINTS] = $hints;
    }

    /**
     * Adds Doctrine query hint.
     *
     * @param string $name  The name of the hint
     * @param mixed  $value The value of the hint
     */
    public function removeHint($name, $value = null)
    {
        $hints    = $this->getHints();
        $toRemove = [];
        if (null === $value) {
            foreach ($hints as $key => $hint) {
                if (is_string($hint) && $hint === $name) {
                    $toRemove[] = $key;
                }
            }
        } else {
            foreach ($hints as $key => $hint) {
                if (is_array($hint) && $hint['name'] === $name && $hint['value'] === $value) {
                    $toRemove[] = $key;
                }
            }
        }

        if (!empty($toRemove)) {
            foreach ($toRemove as $key) {
                unset($hints[$key]);
            }
            if (!empty($hints)) {
                $this->items[self::HINTS] = array_values($hints);
            } else {
                unset($this->items[self::HINTS]);
            }
        }
    }

    /**
     * Gets a handler that should be used to modify serialized data.
     *
     * @return callable|null
     */
    public function getPostSerializeHandler()
    {
        return array_key_exists(self::POST_SERIALIZE, $this->items)
            ? $this->items[self::POST_SERIALIZE]
            : null;
    }

    /**
     * Sets a handler that should be used to modify serialized data.
     *
     * @param callable|null $handler function (array $item) : array
     */
    public function setPostSerializeHandler($handler = null)
    {
        if (null !== $handler) {
            $this->items[self::POST_SERIALIZE] = $handler;
        } else {
            unset($this->items[self::POST_SERIALIZE]);
        }
    }
}
