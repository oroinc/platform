<?php

namespace Oro\Component\EntitySerializer;

/**
 * Represents the configuration of an entity.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityConfig
{
    /** @var string|null */
    protected $exclusionPolicy;

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
        if (null !== $this->exclusionPolicy && ConfigUtil::EXCLUSION_POLICY_NONE !== $this->exclusionPolicy) {
            $result[ConfigUtil::EXCLUSION_POLICY] = $this->exclusionPolicy;
        }
        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldName => $fieldConfig) {
                $result[ConfigUtil::FIELDS][$fieldName] = $fieldConfig->toArray();
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
            null === $this->exclusionPolicy
            && empty($this->items)
            && empty($this->fields);
    }

    /**
     * Make a deep copy of object.
     */
    public function __clone()
    {
        $this->items = ConfigUtil::cloneItems($this->items);
        $this->fields = ConfigUtil::cloneObjects($this->fields);
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
        return \array_key_exists($key, $this->items);
    }

    /**
     * Gets the configuration value.
     *
     * @param string $key
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        if (!\array_key_exists($key, $this->items)) {
            return $defaultValue;
        }

        return $this->items[$key];
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
        if (!isset($this->fields[$fieldName])) {
            return null;
        }

        return $this->fields[$fieldName];
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

    /**
     * Indicates whether using of Doctrine partial object is enabled.
     *
     * @return bool
     */
    public function isPartialLoadEnabled()
    {
        return !$this->get(ConfigUtil::DISABLE_PARTIAL_LOAD, false);
    }

    /**
     * Allows using of Doctrine partial object.
     */
    public function enablePartialLoad()
    {
        unset($this->items[ConfigUtil::DISABLE_PARTIAL_LOAD]);
    }

    /**
     * Prohibits using of Doctrine partial object.
     */
    public function disablePartialLoad()
    {
        $this->items[ConfigUtil::DISABLE_PARTIAL_LOAD] = true;
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
        return $this->get(ConfigUtil::ORDER_BY, []);
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
        if ($orderBy) {
            $this->items[ConfigUtil::ORDER_BY] = $orderBy;
        } else {
            unset($this->items[ConfigUtil::ORDER_BY]);
        }
    }

    /**
     * Gets the maximum number of items in the result.
     *
     * @return int|null The requested maximum number of items or NULL if not limited
     */
    public function getMaxResults()
    {
        return $this->get(ConfigUtil::MAX_RESULTS);
    }

    /**
     * Sets the maximum number of items in the result.
     *
     * @param int|null $maxResults The maximum number of items or NULL to set unlimited
     */
    public function setMaxResults($maxResults = null)
    {
        if (null === $maxResults || $maxResults < 0) {
            unset($this->items[ConfigUtil::MAX_RESULTS]);
        } else {
            $this->items[ConfigUtil::MAX_RESULTS] = $maxResults;
        }
    }

    /**
     * Indicates whether an additional element with
     * key "_" {@see \Oro\Component\EntitySerializer\ConfigUtil::INFO_RECORD_KEY}
     * and value ['has_more' => true] {@see \Oro\Component\EntitySerializer\ConfigUtil::HAS_MORE}
     * should be added to a collection if it has more records than it was requested.
     *
     * @return string An exclusion strategy, e.g. "none" or "all"
     */
    public function getHasMore()
    {
        return (bool)$this->get(ConfigUtil::HAS_MORE, false);
    }

    /**
     * Set a flag indicates whether an additional element with
     * key "_" {@see \Oro\Component\EntitySerializer\ConfigUtil::INFO_RECORD_KEY}
     * and value ['has_more' => true] {@see \Oro\Component\EntitySerializer\ConfigUtil::HAS_MORE}
     * should be added to a collection if it has more records than it was requested.
     *
     * @param bool $hasMore
     */
    public function setHasMore($hasMore)
    {
        if ($hasMore) {
            $this->items[ConfigUtil::HAS_MORE] = true;
        } else {
            unset($this->items[ConfigUtil::HAS_MORE]);
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
        return $this->get(ConfigUtil::HINTS, []);
    }

    /**
     * Adds Doctrine query hint.
     *
     * @param string $name  The name of the hint
     * @param mixed  $value The value of the hint
     */
    public function addHint($name, $value = null)
    {
        $hints = $this->getHints();
        if (null === $value) {
            $hints[] = $name;
        } else {
            $hints[] = ['name' => $name, 'value' => $value];
        }

        $this->items[ConfigUtil::HINTS] = $hints;
    }

    /**
     * Adds Doctrine query hint.
     *
     * @param string $name  The name of the hint
     * @param mixed  $value The value of the hint
     */
    public function removeHint($name, $value = null)
    {
        $hints = $this->getHints();
        $toRemove = [];
        if (null === $value) {
            foreach ($hints as $key => $hint) {
                if (\is_string($hint) && $hint === $name) {
                    $toRemove[] = $key;
                }
            }
        } else {
            foreach ($hints as $key => $hint) {
                if (\is_array($hint) && $hint['name'] === $name && $hint['value'] === $value) {
                    $toRemove[] = $key;
                }
            }
        }

        if (!empty($toRemove)) {
            foreach ($toRemove as $key) {
                unset($hints[$key]);
            }
            if ($hints) {
                $this->items[ConfigUtil::HINTS] = \array_values($hints);
            } else {
                unset($this->items[ConfigUtil::HINTS]);
            }
        }
    }

    /**
     * Gets a handler that should be used to modify serialized data for a single item.
     *
     * @return callable|null
     */
    public function getPostSerializeHandler()
    {
        return $this->get(ConfigUtil::POST_SERIALIZE);
    }

    /**
     * Sets a handler that should be used to modify serialized data for a single item.
     *
     * @param callable|null $handler function (array $item, array $context) : array
     */
    public function setPostSerializeHandler($handler = null)
    {
        if (null !== $handler) {
            $this->items[ConfigUtil::POST_SERIALIZE] = $handler;
        } else {
            unset($this->items[ConfigUtil::POST_SERIALIZE]);
        }
    }

    /**
     * Gets a handler that should be used to modify serialized data for a list of items.
     *
     * @return callable|null
     */
    public function getPostSerializeCollectionHandler()
    {
        return $this->get(ConfigUtil::POST_SERIALIZE_COLLECTION);
    }

    /**
     * Sets a handler that should be used to modify serialized data for a list of items.
     * This handler is executed after each element in the collection
     * is processed by own post serialization handler.
     * @see setPostSerializeHandler
     * IMPORTANT: the items are an associative array and the collection handler must keep
     * keys in this array without changes.
     *
     * @param callable|null $handler function (array $items, array $context) : array
     */
    public function setPostSerializeCollectionHandler($handler = null)
    {
        if (null !== $handler) {
            $this->items[ConfigUtil::POST_SERIALIZE_COLLECTION] = $handler;
        } else {
            unset($this->items[ConfigUtil::POST_SERIALIZE_COLLECTION]);
        }
    }
}
